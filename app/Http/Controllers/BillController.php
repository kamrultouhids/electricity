<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\MeterReading;
use App\Models\Sheet;
use App\Services\BillGenerator;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /**
     * List generated bills with filters (month, sheet, status).
     */
    public function index(Request $request)
    {
        $query = Bill::query()->with(['customer.sheet']);

        if ($search = $request->input('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sheet_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('sheet_id', (int) $request->input('sheet_id'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('month')) {
            [$year, $month] = array_pad(explode('-', $request->input('month')), 2, null);
            if ($year && $month) {
                $query->whereYear('billing_month', $year)
                    ->whereMonth('billing_month', $month);
            }
        }

        $bills = $query->latest('billing_month')->latest('id')
            ->paginate(15)->withQueryString();

        return view('bills.index', [
            'bills'         => $bills,
            'sheets'        => Sheet::orderBy('name')->get(),
            'statusOptions' => Bill::STATUS_LABELS,
            'pendingCount'  => MeterReading::where('status', MeterReading::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Step 1 — list pending meter readings that are ready to be billed.
     */
    public function pending(Request $request)
    {
        $query = MeterReading::query()
            ->with(['customer.sheet'])
            ->where('status', MeterReading::STATUS_PENDING);

        if ($search = $request->input('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sheet_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('sheet_id', (int) $request->input('sheet_id'));
            });
        }

        if ($request->filled('month')) {
            [$year, $month] = array_pad(explode('-', $request->input('month')), 2, null);
            if ($year && $month) {
                $query->whereYear('reading_date', $year)
                    ->whereMonth('reading_date', $month);
            }
        }

        $readings = $query->latest('reading_date')->latest('id')
            ->paginate(15)->withQueryString();

        return view('bills.pending', [
            'readings' => $readings,
            'sheets'   => Sheet::orderBy('name')->get(),
        ]);
    }

    /**
     * Step 2 — preview the computed bill for a reading before generating.
     */
    public function preview(MeterReading $meterReading, BillGenerator $generator)
    {
        $meterReading->load('customer.sheet');

        if (! $meterReading->customer || ! $meterReading->customer->isActive()) {
            return redirect()->route('bills.pending')
                ->with('error', 'This customer is inactive and cannot be billed.');
        }

        if ($generator->alreadyBilled($meterReading)) {
            return redirect()->route('bills.pending')
                ->with('error', 'This customer already has a bill for that month.');
        }

        $customer = $meterReading->customer;

        $previousBills = Bill::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('billing_month')
            ->limit(6)
            ->get();

        $previousReading = MeterReading::query()
            ->where('customer_id', $customer->id)
            ->whereDate('reading_date', '<', $meterReading->reading_date)
            ->latest('reading_date')
            ->first();

        return view('bills.preview', [
            'meterReading'    => $meterReading,
            'data'            => $generator->buildData($meterReading),
            'previousBills'   => $previousBills,
            'previousReading' => $previousReading,
        ]);
    }

    /**
     * Step 3 — generate (persist) the bill.
     */
    public function store(MeterReading $meterReading, BillGenerator $generator)
    {
        $bill = $generator->generateForReading($meterReading, auth()->id());

        if (! $bill) {
            return redirect()->route('bills.pending')
                ->with('error', 'Could not generate a bill (inactive customer or already billed).');
        }

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill generated successfully!');
    }

    /**
     * Show a single bill.
     */
    public function show(Bill $bill)
    {
        $bill->load(['customer.sheet', 'meterReading', 'createdBy', 'updatedBy']);

        return view('bills.show', compact('bill'));
    }
}
