<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Reports landing page.
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Daily collection report — payments grouped by day.
     */
    public function dailyCollection(Request $request)
    {
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to = $request->input('to_date', now()->endOfMonth()->toDateString());

        $query = Payment::query()
            ->selectRaw('DATE(payment_date) as day, COUNT(*) as cnt, SUM(amount) as total_amount, SUM(discount) as total_discount')
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('status', Payment::STATUS_COMPLETED)
            ->groupBy('day')
            ->orderBy('day');

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('collector_id')) {
            $query->where('collector_id', (int) $request->input('collector_id'));
        }

        $rows = $query->get();

        if ($request->input('export') === 'csv') {
            return $this->exportCsv('daily-collection-report.csv',
                ['Date', 'Payments', 'Amount', 'Discount', 'Total Settled'],
                $rows->map(fn ($r) => [
                    $r->day,
                    $r->cnt,
                    number_format((float) $r->total_amount, 2, '.', ''),
                    number_format((float) $r->total_discount, 2, '.', ''),
                    number_format((float) $r->total_amount + (float) $r->total_discount, 2, '.', ''),
                ])
            );
        }

        return view('reports.daily_collection', [
            'rows'       => $rows,
            'from'       => $from,
            'to'         => $to,
            'methods'    => Payment::METHODS,
            'collectors' => $this->collectors(),
        ]);
    }

    /**
     * Monthly collection report — payments grouped by month.
     */
    public function monthlyCollection(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $query = Payment::query()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, COUNT(*) as cnt, SUM(amount) as total_amount, SUM(discount) as total_discount")
            ->whereYear('payment_date', $year)
            ->where('status', Payment::STATUS_COMPLETED)
            ->groupBy('ym')
            ->orderBy('ym');

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        $rows = $query->get();

        if ($request->input('export') === 'csv') {
            return $this->exportCsv("monthly-collection-report-{$year}.csv",
                ['Month', 'Payments', 'Amount', 'Discount', 'Total Settled'],
                $rows->map(fn ($r) => [
                    \Illuminate\Support\Carbon::parse($r->ym . '-01')->format('M Y'),
                    $r->cnt,
                    number_format((float) $r->total_amount, 2, '.', ''),
                    number_format((float) $r->total_discount, 2, '.', ''),
                    number_format((float) $r->total_amount + (float) $r->total_discount, 2, '.', ''),
                ])
            );
        }

        return view('reports.monthly_collection', [
            'rows'    => $rows,
            'year'    => $year,
            'years'   => $this->paymentYears(),
            'methods' => Payment::METHODS,
        ]);
    }

    /**
     * Customer report — per-customer collection, consumption and outstanding.
     */
    public function customer(Request $request)
    {
        $query = Customer::query()
            ->with('sheet')
            ->withSum('payments as paid_total', 'amount')
            ->withSum('payments as discount_total', 'discount')
            ->withSum('readings as consumption_total', 'consumed_units')
            ->addSelect(['outstanding' => Bill::query()
                ->selectRaw('due_amount')
                ->whereColumn('customer_id', 'customers.id')
                ->orderByDesc('billing_month')
                ->orderByDesc('id')
                ->limit(1),
            ]);

        $this->applyCustomerFilters($query, $request);

        $query->orderBy('serial_no');

        if ($request->input('export') === 'csv') {
            return $this->exportCsv('customer-report.csv',
                ['Serial', 'Name', 'Sheet', 'Meter', 'Mobile', 'Consumption (units)', 'Collected', 'Discount', 'Outstanding'],
                $query->get()->map(fn ($c) => [
                    $c->serial_no,
                    $c->name,
                    $c->sheet->name ?? '',
                    $c->meter_number,
                    $c->mobile_number,
                    number_format((float) $c->consumption_total, 2, '.', ''),
                    number_format((float) $c->paid_total, 2, '.', ''),
                    number_format((float) $c->discount_total, 2, '.', ''),
                    number_format((float) $c->outstanding, 2, '.', ''),
                ])
            );
        }

        return view('reports.customer', [
            'customers' => $query->paginate(20)->withQueryString(),
            'sheets'    => Sheet::orderBy('name')->get(),
        ]);
    }

    /**
     * Unit consumption report — consumed units per customer over a period.
     */
    public function unitConsumption(Request $request)
    {
        $query = MeterReading::query()
            ->selectRaw('customer_id, COUNT(*) as readings_count, SUM(consumed_units) as total_units, MAX(reading_date) as last_reading')
            ->with('customer.sheet')
            ->groupBy('customer_id');

        if ($request->filled('from_date')) {
            $query->whereDate('reading_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('reading_date', '<=', $request->input('to_date'));
        }

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

        $query->orderByDesc('total_units');

        if ($request->input('export') === 'csv') {
            return $this->exportCsv('unit-consumption-report.csv',
                ['Serial', 'Name', 'Sheet', 'Meter', 'Readings', 'Total Units', 'Last Reading'],
                $query->get()->map(fn ($r) => [
                    $r->customer->serial_no ?? '',
                    $r->customer->name ?? '',
                    $r->customer->sheet->name ?? '',
                    $r->customer->meter_number ?? '',
                    $r->readings_count,
                    number_format((float) $r->total_units, 2, '.', ''),
                    $r->last_reading,
                ])
            );
        }

        return view('reports.unit_consumption', [
            'rows'   => $query->paginate(20)->withQueryString(),
            'sheets' => Sheet::orderBy('name')->get(),
            'from'   => $request->input('from_date'),
            'to'     => $request->input('to_date'),
        ]);
    }

    /**
     * Outstanding balance report — customers whose latest bill still has a due.
     */
    public function outstanding(Request $request)
    {
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

        $query->orderByDesc('bills.due_amount');

        if ($request->input('export') === 'csv') {
            return $this->exportCsv('outstanding-balance-report.csv',
                ['Serial', 'Name', 'Sheet', 'Mobile', 'Meter', 'Last Billing Month', 'Outstanding'],
                $query->get()->map(fn ($b) => [
                    $b->customer->serial_no ?? '',
                    $b->customer->name ?? '',
                    $b->customer->sheet->name ?? '',
                    $b->customer->mobile_number ?? '',
                    $b->customer->meter_number ?? '',
                    optional($b->billing_month)->format('M Y'),
                    number_format((float) $b->due_amount, 2, '.', ''),
                ])
            );
        }

        return view('reports.outstanding', [
            'bills'  => $query->paginate(20)->withQueryString(),
            'sheets' => Sheet::orderBy('name')->get(),
            'total'  => (float) (clone $query)->sum('bills.due_amount'),
        ]);
    }

    /**
     * Income & expense report — monthly collections vs expenses over a period.
     */
    public function incomeExpense(Request $request)
    {
        $from = $request->input('from_date', now()->startOfYear()->toDateString());
        $to = $request->input('to_date', now()->endOfMonth()->toDateString());

        $income = Payment::query()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, SUM(amount) as total")
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('status', Payment::STATUS_COMPLETED)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $expense = Expense::query()
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(amount) as total")
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $months = $income->keys()->merge($expense->keys())->unique()->sort()->values();

        $rows = $months->map(function ($ym) use ($income, $expense) {
            $inc = (float) ($income[$ym] ?? 0);
            $exp = (float) ($expense[$ym] ?? 0);

            return [
                'ym'      => $ym,
                'income'  => $inc,
                'expense' => $exp,
                'net'     => round($inc - $exp, 2),
            ];
        });

        $totalIncome = (float) $income->sum();
        $totalExpense = (float) $expense->sum();

        if ($request->input('export') === 'csv') {
            return $this->exportCsv('income-expense-report.csv',
                ['Month', 'Income', 'Expense', 'Net'],
                $rows->map(fn ($r) => [
                    \Illuminate\Support\Carbon::parse($r['ym'] . '-01')->format('M Y'),
                    number_format($r['income'], 2, '.', ''),
                    number_format($r['expense'], 2, '.', ''),
                    number_format($r['net'], 2, '.', ''),
                ])
            );
        }

        return view('reports.income_expense', [
            'rows'         => $rows,
            'from'         => $from,
            'to'           => $to,
            'totalIncome'  => $totalIncome,
            'totalExpense' => $totalExpense,
            'net'          => round($totalIncome - $totalExpense, 2),
        ]);
    }

    /**
     * Apply shared search / sheet / status filters to a customer query.
     */
    protected function applyCustomerFilters($query, Request $request): void
    {
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sheet_id')) {
            $query->where('sheet_id', (int) $request->input('sheet_id'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', (int) $request->input('status'));
        }
    }

    /**
     * Users who have collected at least one payment (for the collector filter).
     */
    protected function collectors()
    {
        return \App\Models\User::query()
            ->whereIn('id', Payment::query()->distinct()->pluck('collector_id')->filter())
            ->orderBy('name')
            ->get();
    }

    /**
     * Distinct years that have payments (for the year filter), newest first.
     */
    protected function paymentYears(): array
    {
        $years = Payment::query()
            ->selectRaw('DISTINCT YEAR(payment_date) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();

        $current = now()->year;

        if (! in_array($current, $years, true)) {
            array_unshift($years, $current);
        }

        return $years;
    }

    /**
     * Stream an array of rows as a CSV download (Excel-compatible).
     */
    protected function exportCsv(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders accented characters correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, (array) $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
