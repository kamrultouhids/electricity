<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\MeterReading;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardController extends Controller
{
    /**
     * Customer portal dashboard.
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $customer->loadMissing('sheet');

        $year = now()->year;

        // --- Monthly consumption for the current year (Jan–Dec) ---
        $byMonth = MeterReading::query()
            ->selectRaw('MONTH(reading_date) as m, SUM(consumed_units) as total')
            ->where('customer_id', $customer->id)
            ->whereYear('reading_date', $year)
            ->groupBy('m')
            ->pluck('total', 'm');

        $months = collect(range(1, 12));
        $consumptionSeries = $months->map(fn ($m) => round((float) ($byMonth[$m] ?? 0), 2))->all();

        // --- Last 12 months of bills ---
        $bills = Bill::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('billing_month')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        // --- Payment history ---
        $payments = Payment::query()
            ->where('customer_id', $customer->id)
            ->with('collector')
            ->latest('payment_date')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('portal.dashboard', [
            'customer'          => $customer,
            'year'              => $year,
            'currentMonth'      => now()->month,
            'consumptionSeries' => $consumptionSeries,
            'bills'             => $bills,
            'payments'          => $payments,
        ]);
    }

    /**
     * Show a printable bill document for one of the customer's own bills.
     */
    public function bill(Bill $bill)
    {
        $customer = Auth::guard('customer')->user();

        if ($bill->customer_id !== $customer->id) {
            throw new NotFoundHttpException();
        }

        $bill->load(['customer.sheet', 'meterReading', 'createdBy']);

        $previousBills = Bill::query()
            ->where('customer_id', $bill->customer_id)
            ->whereDate('billing_month', '<', $bill->billing_month)
            ->orderByDesc('billing_month')
            ->limit(3)
            ->get();

        $previousReading = $bill->meterReading
            ? MeterReading::query()
                ->where('customer_id', $bill->customer_id)
                ->whereDate('reading_date', '<', $bill->meterReading->reading_date)
                ->latest('reading_date')
                ->first()
            : null;

        return view('portal.bill', compact('bill', 'previousBills', 'previousReading'));
    }

    /**
     * Show a printable receipt for one of the customer's own payments.
     */
    public function receipt(Payment $payment)
    {
        $customer = Auth::guard('customer')->user();

        // A customer may only view their own receipts.
        if ($payment->customer_id !== $customer->id) {
            throw new NotFoundHttpException();
        }

        $payment->load(['customer.sheet', 'collector', 'allocations.bill']);

        return view('portal.receipt', compact('payment'));
    }
}
