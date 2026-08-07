<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\BillCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List payments with filters (search, method, date range).
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
     * Show the collect-payment form for a customer (all their unpaid bills).
     */
    public function create(Customer $customer)
    {
        $unpaidBills = $this->unpaidBills($customer);

        if ($unpaidBills->isEmpty()) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'This customer has no unpaid bills.');
        }

        return view('payments.create', [
            'customer'    => $customer,
            'unpaidBills' => $unpaidBills,
            'totalDue'    => round((float) $unpaidBills->sum('due_amount'), 2),
            'methods'     => Payment::METHODS,
        ]);
    }

    /**
     * Record a payment and allocate it across unpaid bills, oldest first.
     */
    public function store(Request $request, Customer $customer, BillCalculator $calculator)
    {
        $unpaidBills = $this->unpaidBills($customer);
        $totalDue = round((float) $unpaidBills->sum('due_amount'), 2);

        if ($totalDue <= 0) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'This customer has no unpaid bills.');
        }

        $data = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', "max:{$totalDue}"],
            'payment_date' => 'required|date',
            'method'       => 'required|in:' . implode(',', array_keys(Payment::METHODS)),
            'note'         => 'nullable|string|max:255',
        ], [
            'amount.max' => "Amount cannot exceed the total due ({$totalDue}).",
        ]);

        $payment = DB::transaction(function () use ($data, $customer, $unpaidBills, $calculator) {
            $payment = Payment::create([
                'customer_id'  => $customer->id,
                'amount'       => $data['amount'],
                'payment_date' => $data['payment_date'],
                'collector_id' => auth()->id(),
                'method'       => $data['method'],
                'note'         => $data['note'] ?? null,
                'status'       => Payment::STATUS_COMPLETED,
                'created_by'   => auth()->id(),
                'updated_by'   => auth()->id(),
            ]);

            $remaining = round((float) $data['amount'], 2);

            // Apply oldest first until the payment is exhausted.
            foreach ($unpaidBills as $bill) {
                if ($remaining <= 0) {
                    break;
                }

                $applied = min($remaining, (float) $bill->due_amount);
                $applied = round($applied, 2);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'bill_id'    => $bill->id,
                    'amount'     => $applied,
                ]);

                $newPaid = round((float) $bill->paid_amount + $applied, 2);
                $total = (float) $bill->total_amount;

                $bill->update([
                    'paid_amount' => $newPaid,
                    'due_amount'  => round($total - $newPaid, 2),
                    'status'      => $calculator->status($total, $newPaid),
                    'updated_by'  => auth()->id(),
                ]);

                $remaining = round($remaining - $applied, 2);
            }

            return $payment;
        });

        return redirect()->route('payments.receipt', $payment)
            ->with('success', 'Payment recorded and allocated successfully!');
    }

    /**
     * Printable payment receipt (with per-bill allocation breakdown).
     */
    public function receipt(Payment $payment)
    {
        $payment->load(['customer.sheet', 'collector', 'allocations.bill']);

        return view('payments.receipt', compact('payment'));
    }

    /**
     * Unpaid / partial bills for a customer, oldest month first.
     */
    protected function unpaidBills(Customer $customer)
    {
        return Bill::query()
            ->where('customer_id', $customer->id)
            ->where('status', '!=', Bill::STATUS_PAID)
            ->where('due_amount', '>', 0)
            ->orderBy('billing_month')
            ->orderBy('id')
            ->get();
    }
}
