<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\MeterReading;
use App\Models\Sheet;
use App\Services\BillCalculator;
use App\Services\BillGenerator;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /** Rows-per-page choices offered on the bill list — also caps a print run. */
    public const PER_PAGE_OPTIONS = [15, 25, 50, 100];

    /**
     * List generated bills with filters (month, sheet, status, rows per page).
     */
    public function index(Request $request)
    {
        $perPage = $this->perPage($request);

        $bills = $this->filteredBills($request)
            ->with(['customer.sheet'])
            ->latest('billing_month')->latest('id')
            ->paginate($perPage)->withQueryString();

        return view('bills.index', [
            'bills'          => $bills,
            'sheets'         => Sheet::orderBy('id')->get(),
            'statusOptions'  => Bill::STATUS_LABELS,
            'pendingCount'   => MeterReading::where('status', MeterReading::STATUS_PENDING)->count(),
            'perPage'        => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * Rows per page, restricted to the offered choices.
     */
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', self::PER_PAGE_OPTIONS[0]);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true)
            ? $perPage
            : self::PER_PAGE_OPTIONS[0];
    }

    /**
     * The bill list filters (search, sheet, status, month), shared by the list
     * and the bulk print so both always cover exactly the same bills.
     */
    protected function filteredBills(Request $request)
    {
        $query = Bill::query();

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

        return $query;
    }

    /**
     * Print the bills currently on screen — same filters, same rows-per-page and
     * page, same order — as full bill documents, one per sheet of paper.
     */
    public function printAll(Request $request)
    {
        $bills = $this->filteredBills($request)
            // An opening balance has no units, no rate and no reading — printed
            // as a bill document it would be a blank sheet. It is shown on its
            // own page instead, and carried into the next real bill regardless.
            ->where('is_opening', false)
            ->with(['customer.sheet', 'meterReading', 'createdBy'])
            ->latest('billing_month')->latest('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($bills->isEmpty()) {
            return redirect()->route('bills.index', $request->query())
                ->with('error', 'No bills match the current filter.');
        }

        // Every reading for the customers on this run, so each bill can name the
        // month of its preceding reading without a query per bill.
        $readingsByCustomer = MeterReading::query()
            ->whereIn('customer_id', $bills->pluck('customer_id')->unique())
            ->orderBy('reading_date')
            ->get()
            ->groupBy('customer_id');

        return view('bills.print_all', [
            'bills'              => $bills,
            'readingsByCustomer' => $readingsByCustomer,
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
            'sheets'   => Sheet::orderBy('id')->get(),
        ]);
    }

    /**
     * One-click bulk generate — generate bills for every pending reading that
     * matches the current filters (oldest reading first so outstanding chains
     * and the oldest-first rule stay correct).
     */
    public function generateAll(Request $request, BillGenerator $generator)
    {
        // One deadline for the whole run — the operator enters it next to the
        // Generate All button.
        $data = $request->validate([
            'bill_last_date' => 'required|date',
        ]);

        $query = MeterReading::query()
            ->with('customer')
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
            $query->whereHas('customer', fn ($q) => $q->where('sheet_id', (int) $request->input('sheet_id')));
        }

        if ($request->filled('month')) {
            [$year, $month] = array_pad(explode('-', $request->input('month')), 2, null);
            if ($year && $month) {
                $query->whereYear('reading_date', $year)->whereMonth('reading_date', $month);
            }
        }

        // Oldest first: guarantees each customer's earlier readings bill before
        // later ones, so previous_outstanding is computed correctly.
        $readings = $query->orderBy('reading_date')->orderBy('id')->get();

        $generated = 0;
        $skipped = 0;
        foreach ($readings as $reading) {
            $generator->generateForReading($reading, auth()->id(), $data['bill_last_date'])
                ? $generated++
                : $skipped++;
        }

        $message = "Generated {$generated} bill(s).".($skipped ? " Skipped {$skipped} (inactive or already billed)." : '');

        return redirect()->route('bills.pending', $request->only('search', 'sheet_id', 'month'))
            ->with($generated ? 'success' : 'error', $generated ? $message : 'No bills were generated. '.$message);
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

        if ($older = $this->olderPendingReading($meterReading)) {
            return redirect()->route('bills.pending')
                ->with('error', "Generate the oldest pending reading first — {$older->reading_date->format('M Y')}.");
        }

        $customer = $meterReading->customer;

        $data = $generator->buildData($meterReading);

        // Preview the exact history that will be frozen onto the bill.
        $previousBills = Bill::mapHistoryRows($data['previous_bills_snapshot']);

        $previousReading = MeterReading::query()
            ->where('customer_id', $customer->id)
            ->whereDate('reading_date', '<', $meterReading->reading_date)
            ->latest('reading_date')
            ->first();

        return view('bills.preview', [
            'meterReading'    => $meterReading,
            'data'            => $data,
            'previousBills'   => $previousBills,
            'previousReading' => $previousReading,
        ]);
    }

    /**
     * Step 3 — generate (persist) the bill.
     */
    public function store(Request $request, MeterReading $meterReading, BillGenerator $generator)
    {
        if ($older = $this->olderPendingReading($meterReading)) {
            return redirect()->route('bills.pending')
                ->with('error', "Generate the oldest pending reading first — {$older->reading_date->format('M Y')}.");
        }

        // The payment deadline printed on the bill, entered on the preview page.
        $data = $request->validate([
            'bill_last_date' => 'required|date',
        ]);

        $bill = $generator->generateForReading($meterReading, auth()->id(), $data['bill_last_date']);

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
        $bill->load(['customer.sheet', 'meterReading', 'createdBy', 'updatedBy', 'revisions.changedBy']);

        $previousBills = $bill->historyRows();

        $previousReading = $bill->meterReading
            ? MeterReading::query()
                ->where('customer_id', $bill->customer_id)
                ->whereDate('reading_date', '<', $bill->meterReading->reading_date)
                ->latest('reading_date')
                ->first()
            : null;

        return view('bills.show', compact('bill', 'previousBills', 'previousReading'));
    }

    /**
     * Show the revise form for a bill — correct the current reading and see
     * what it does to the charges before saving.
     */
    public function revise(Bill $bill, BillCalculator $calculator)
    {
        $bill->load(['customer.sheet', 'meterReading', 'revisions.changedBy']);

        if ($reason = $bill->revisionBlockedReason()) {
            return redirect()->route('bills.show', $bill)->with('error', $reason);
        }

        return view('bills.revise', [
            'bill' => $bill,
            // Feeds the live preview so it floors exactly like the server does.
            'minimumCharge' => $calculator->minimumCharge($bill->customer->connection_type),
        ]);
    }

    /**
     * Apply the corrected reading to the bill.
     */
    public function storeRevision(Request $request, Bill $bill, BillGenerator $generator)
    {
        $bill->load(['customer', 'meterReading']);

        if ($reason = $bill->revisionBlockedReason()) {
            return redirect()->route('bills.show', $bill)->with('error', $reason);
        }

        $data = $request->validate([
            'previous_reading'     => 'required|numeric|min:0',
            'current_reading'      => 'required|numeric|min:0',
            'previous_outstanding' => 'required|numeric|min:0',
            'reason'               => 'required|string|max:255',
        ]);

        $previous = round((float) $data['previous_reading'], 2);
        $current = round((float) $data['current_reading'], 2);
        $outstanding = round((float) $data['previous_outstanding'], 2);

        if ($current < $previous) {
            return back()->withInput()->withErrors([
                'current_reading' => "Current reading must be greater than or equal to the previous reading ({$previous}).",
            ]);
        }

        $unchanged = $previous === (float) $bill->meterReading->previous_reading
            && $current === (float) $bill->meterReading->current_reading
            && $outstanding === (float) $bill->previous_outstanding;

        if ($unchanged) {
            return back()->withInput()->withErrors([
                'current_reading' => 'These are the figures already on the bill — nothing to revise.',
            ]);
        }

        $generator->revise($bill, $current, $data['reason'], auth()->id(), $previous, $outstanding);

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill revised from the corrected reading. Reprint it for the customer.');
    }

    /**
     * The customer's oldest pending reading, if it isn't the one being billed.
     * Enforces "bill the oldest month first".
     */
    protected function olderPendingReading(MeterReading $reading): ?MeterReading
    {
        $oldest = MeterReading::query()
            ->where('customer_id', $reading->customer_id)
            ->where('status', MeterReading::STATUS_PENDING)
            ->orderBy('reading_date')
            ->orderBy('id')
            ->first();

        return ($oldest && $oldest->id !== $reading->id) ? $oldest : null;
    }
}
