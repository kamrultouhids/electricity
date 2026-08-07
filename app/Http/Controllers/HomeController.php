<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\MeterReading;
use App\Models\Payment;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $year = now()->year;

        // --- Summary cards ---
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', Customer::STATUS_ACTIVE)->count();
        $inactiveCustomers = Customer::where('status', Customer::STATUS_INACTIVE)->count();

        $todayCollection = (float) Payment::query()
            ->whereDate('payment_date', now()->toDateString())
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $monthCollection = (float) Payment::query()
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', now()->month)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $totalOutstanding = (float) $this->latestBillsWithDue()->sum('bills.due_amount');

        $unitsThisMonth = (float) MeterReading::query()
            ->whereYear('reading_date', $year)
            ->whereMonth('reading_date', now()->month)
            ->sum('consumed_units');

        // --- Income / Expense / Net (all-time) ---
        $totalIncome = (float) Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $totalExpense = (float) Expense::sum('amount');
        $netProfit = round($totalIncome - $totalExpense, 2);

        // --- Charts (Jan–Dec of the current year) ---
        $collectionByMonth = Payment::query()
            ->selectRaw('MONTH(payment_date) as m, SUM(amount) as total')
            ->whereYear('payment_date', $year)
            ->where('status', Payment::STATUS_COMPLETED)
            ->groupBy('m')
            ->pluck('total', 'm');

        $consumptionByMonth = MeterReading::query()
            ->selectRaw('MONTH(reading_date) as m, SUM(consumed_units) as total')
            ->whereYear('reading_date', $year)
            ->groupBy('m')
            ->pluck('total', 'm');

        $months = collect(range(1, 12));
        $collectionSeries = $months->map(fn ($m) => round((float) ($collectionByMonth[$m] ?? 0), 2))->all();
        $consumptionSeries = $months->map(fn ($m) => round((float) ($consumptionByMonth[$m] ?? 0), 2))->all();

        // --- Recent payments ---
        $recentPayments = Payment::query()
            ->with(['customer', 'createdBy'])
            ->latest('payment_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('home', [
            'totalCustomers'    => $totalCustomers,
            'activeCustomers'   => $activeCustomers,
            'inactiveCustomers' => $inactiveCustomers,
            'todayCollection'   => $todayCollection,
            'monthCollection'   => $monthCollection,
            'totalOutstanding'  => $totalOutstanding,
            'unitsThisMonth'    => $unitsThisMonth,
            'totalIncome'       => $totalIncome,
            'totalExpense'      => $totalExpense,
            'netProfit'         => $netProfit,
            'year'              => $year,
            'currentMonth'      => now()->month,
            'collectionSeries'  => $collectionSeries,
            'consumptionSeries' => $consumptionSeries,
            'recentPayments'    => $recentPayments,
        ]);
    }

    /**
     * Query for each customer's latest bill that still carries a due
     * (the same "true outstanding" logic used by the due list / reports).
     */
    protected function latestBillsWithDue()
    {
        $latestPerCustomer = Bill::query()
            ->selectRaw('customer_id, MAX(billing_month) as max_month')
            ->groupBy('customer_id');

        return Bill::query()
            ->select('bills.*')
            ->joinSub($latestPerCustomer, 'lb', function ($join) {
                $join->on('bills.customer_id', '=', 'lb.customer_id')
                    ->on('bills.billing_month', '=', 'lb.max_month');
            })
            ->where('bills.due_amount', '>', 0);
    }
}
