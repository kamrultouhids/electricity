<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Customers with outstanding due (based on their latest bill, which already
     * carries forward all previous months' due).
     */
    public function dueList(Request $request)
    {
        // Latest billing month per customer.
        $latestPerCustomer = Bill::query()
            ->selectRaw('customer_id, MAX(billing_month) as max_month')
            ->groupBy('customer_id');

        $query = Bill::query()
            ->select('bills.*')
            ->joinSub($latestPerCustomer, 'lb', function ($join) {
                $join->on('bills.customer_id', '=', 'lb.customer_id')
                    ->on('bills.billing_month', '=', 'lb.max_month');
            })
            ->where('bills.due_amount', '>', 0)
            ->with('customer.sheet');

        if ($search = $request->input('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sheet_id')) {
            $query->whereHas('customer', fn ($q) => $q->where('sheet_id', (int) $request->input('sheet_id')));
        }

        $bills = $query->orderByDesc('bills.due_amount')
            ->paginate(15)->withQueryString();

        return view('payments.due', [
            'bills'  => $bills,
            'sheets' => Sheet::orderBy('name')->get(),
        ]);
    }

    /**
     * List recorded payments with filters (search, method, date range).
     */
    public function index(Request $request)
    {
        $query = Payment::query()->with(['customer', 'collector']);

        if ($search = $request->input('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->input('to_date'));
        }

        $payments = $query->latest('payment_date')->latest('id')
            ->paginate(15)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'methods'  => Payment::METHODS,
        ]);
    }

    /**
     * Show the collect-payment form (against the customer's latest due bill).
     */
    public function create(Customer $customer)
    {
        $bill = $this->latestDueBill($customer);

        if (! $bill) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'This customer has no due.');
        }

        $bill->load('customer.sheet');

        return view('payments.create', [
            'customer' => $customer,
            'bill'     => $bill,
            'methods'  => Payment::METHODS,
        ]);
    }

    /**
     * Record a payment (amount + optional discount) against the latest due bill.
     */
    public function store(Request $request, Customer $customer)
    {
        $bill = $this->latestDueBill($customer);

        if (! $bill) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'This customer has no due.');
        }

        $due = (float) $bill->due_amount;

        $data = $request->validate([
            'amount'       => 'required|numeric|min:0',
            'discount'     => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'method'       => 'required|in:' . implode(',', array_keys(Payment::METHODS)),
            'note'         => 'nullable|string|max:255',
        ]);

        $amount = round((float) $data['amount'], 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $settle = round($amount + $discount, 2);

        if ($settle < 0.01) {
            return back()->withInput()->withErrors(['amount' => 'Amount and discount cannot both be zero.']);
        }

        if ($settle > $due) {
            return back()->withInput()->withErrors([
                'amount' => "Amount + discount ({$settle}) cannot exceed the due amount ({$due}).",
            ]);
        }

        $payment = DB::transaction(function () use ($data, $customer, $bill, $amount, $discount) {
            $payment = Payment::create([
                'customer_id'  => $customer->id,
                'amount'       => $amount,
                'discount'     => $discount,
                'payment_date' => $data['payment_date'],
                'collector_id' => auth()->id(),
                'method'       => $data['method'],
                'note'         => $data['note'] ?? null,
                'status'       => Payment::STATUS_COMPLETED,
                'created_by'   => auth()->id(),
                'updated_by'   => auth()->id(),
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'bill_id'    => $bill->id,
                'amount'     => $amount,
            ]);

            $total = (float) $bill->total_amount;
            $newPaid = round((float) $bill->paid_amount + $amount, 2);
            $newDiscount = round((float) $bill->discount + $discount, 2);
            $newDue = round($total - $newPaid - $newDiscount, 2);

            $bill->update([
                'paid_amount' => $newPaid,
                'discount'    => $newDiscount,
                'due_amount'  => $newDue,
                'status'      => $newDue <= 0 ? Bill::STATUS_PAID : Bill::STATUS_PARTIAL,
                'updated_by'  => auth()->id(),
            ]);

            return $payment;
        });

        return redirect()->route('payments.receipt', $payment)
            ->with('success', 'Payment recorded successfully!');
    }

    /**
     * Printable payment receipt.
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['customer.sheet', 'collector', 'allocations.bill']);

        return view('payments.receipt', compact('payment'));
    }

    /**
     * The customer's latest bill that still has a due (the true outstanding).
     */
    protected function latestDueBill(Customer $customer): ?Bill
    {
        return Bill::query()
            ->where('customer_id', $customer->id)
            ->where('due_amount', '>', 0)
            ->orderByDesc('billing_month')
            ->orderByDesc('id')
            ->first();
    }
}
