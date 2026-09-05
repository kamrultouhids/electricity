<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Sheet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /** Rows-per-page choices offered on the payments list. */
    public const PER_PAGE_OPTIONS = [15, 25, 50, 100];

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

        $totalDue = (float) (clone $query)->sum('bills.due_amount');

        $bills = $query->orderByDesc('bills.due_amount')
            ->paginate(15)->withQueryString();

        return view('payments.due', [
            'bills'    => $bills,
            'sheets'   => Sheet::orderBy('id')->get(),
            'totalDue' => $totalDue,
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

        if ($request->filled('collector_id')) {
            $query->where('collector_id', (int) $request->input('collector_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->input('to_date'));
        }

        // Rows per page, limited to the options offered in the filter bar.
        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 15;
        }

        $payments = $query->latest('payment_date')->latest('id')
            ->paginate($perPage)->withQueryString();

        return view('payments.index', [
            'payments'       => $payments,
            'methods'        => Payment::METHODS,
            'collectors'     => User::where('email', '!=', 'superadmin@gmail.com')->orderBy('name')->get(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Collect a payment: search for the customer by serial no, name, mobile or
     * meter no, pick one, and record the payment — all on the one page.
     */
    public function collect(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $customers = collect();
        $dueBills = collect();

        // The picked customer's payment panel is rendered inline below the search.
        $selectedCustomer = $request->filled('customer')
            ? Customer::with('sheet')->find($request->integer('customer'))
            : null;

        if ($search !== '') {
            $customers = Customer::query()
                ->with('sheet')
                ->where(function ($q) use ($search) {
                    $q->where('serial_no', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('meter_number', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();

            // Latest due bill per matched customer — ascending so keyBy keeps the newest.
            $dueBills = Bill::query()
                ->whereIn('customer_id', $customers->pluck('id'))
                ->where('due_amount', '>', 0)
                ->orderBy('billing_month')
                ->orderBy('id')
                ->get()
                ->keyBy('customer_id');

            // One match is unambiguous — open its payment form straight away
            // instead of making the collector click through a single row.
            if (! $selectedCustomer && $customers->total() === 1) {
                $selectedCustomer = $customers->first();
            }
        }

        $selectedBill = $selectedCustomer ? $this->latestDueBill($selectedCustomer) : null;

        return view('payments.collect', [
            'search'    => $search,
            'customers' => $customers,
            'dueBills'  => $dueBills,
            'customer'  => $selectedCustomer,
            'bill'      => $selectedBill,
            'methods'   => Payment::METHODS,
        ]);
    }

    /**
     * Kept for the links that point straight at a customer (due list, customer
     * page) — collecting happens on the one collect page.
     */
    public function create(Customer $customer)
    {
        return redirect()->route('payments.collect', ['customer' => $customer->id]);
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

            // The latest bill's due already carried forward all previous months,
            // so the same money settles those earlier bills as well.
            $this->settleEarlierBills($customer, $bill, round($amount + $discount, 2));

            return $payment;
        });

        // Stay on the collect page (with the search intact) so the next payment
        // can be taken right away; the receipt is one click from the flash.
        return redirect()->route('payments.collect', array_filter([
            'customer' => $customer->id,
            'search'   => $request->input('search'),
        ]))
            ->with('success', 'Payment recorded successfully!')
            ->with('receipt_payment_id', $payment->id);
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
     * Spread the settled amount over the customer's earlier due bills, oldest
     * first, capped at each bill's own due. A bill is only marked Paid when the
     * amount actually covers it — anything less leaves it Partial with the
     * remainder still showing as due.
     */
    protected function settleEarlierBills(Customer $customer, Bill $currentBill, float $settled): void
    {
        $earlierBills = Bill::query()
            ->where('customer_id', $customer->id)
            ->whereDate('billing_month', '<', $currentBill->billing_month)
            ->where('due_amount', '>', 0)
            ->orderBy('billing_month')
            ->orderBy('id')
            ->get();

        $remaining = $settled;

        foreach ($earlierBills as $earlier) {
            if ($remaining < 0.01) {
                break;
            }

            $applied = min($remaining, (float) $earlier->due_amount);
            $newDue = round((float) $earlier->due_amount - $applied, 2);

            $earlier->update([
                'paid_amount' => round((float) $earlier->paid_amount + $applied, 2),
                'due_amount'  => $newDue,
                'status'      => $newDue <= 0 ? Bill::STATUS_PAID : Bill::STATUS_PARTIAL,
                'updated_by'  => auth()->id(),
            ]);

            $remaining = round($remaining - $applied, 2);
        }
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
