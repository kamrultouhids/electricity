<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Services\ImageService;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MeterReadingController extends Controller
{
    /**
     * List readings with filters (sheet, customer, month).
     */
    public function index(Request $request)
    {
        $query = MeterReading::query()->with(['customer.sheet', 'createdBy', 'updatedBy']);

        // Search by the customer's serial no, name, mobile or meter no
        if ($search = $request->input('search')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        // Filter by sheet (through the customer)
        if ($request->filled('sheet_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('sheet_id', (int) $request->input('sheet_id'));
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        // Filter by month (YYYY-MM)
        if ($request->filled('month')) {
            [$year, $month] = array_pad(explode('-', $request->input('month')), 2, null);
            if ($year && $month) {
                $query->whereYear('reading_date', $year)
                    ->whereMonth('reading_date', $month);
            }
        }

        $readings = $query->latest()
            ->paginate(15)->withQueryString();

        $this->flagDiscrepancies($readings->getCollection());

        return view('meter_readings.index', [
            'readings'      => $readings,
            'sheets'        => Sheet::orderBy('name')->get(),
            'statusOptions' => MeterReading::STATUS_LABELS,
        ]);
    }

    /**
     * Mark each reading that breaks the meter chain: this reading's
     * previous_reading should equal the previous (older) reading's
     * current_reading for the same customer. Also flags a reading whose
     * current is below its own previous. Sets a dynamic `is_flagged`
     * attribute on each reading (the flag lands on the newer row).
     */
    protected function flagDiscrepancies($readings): void
    {
        if ($readings->isEmpty()) {
            return;
        }

        $customerIds = $readings->pluck('customer_id')->unique()->all();

        // Full ordered chain per customer (oldest -> newest).
        $chains = MeterReading::query()
            ->whereIn('customer_id', $customerIds)
            ->orderBy('customer_id')
            ->orderBy('reading_date')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'reading_date', 'previous_reading', 'current_reading'])
            ->groupBy('customer_id');

        foreach ($readings as $reading) {
            $chain = $chains->get($reading->customer_id);
            $flagged = (float) $reading->current_reading < (float) $reading->previous_reading;
            $expected = null;   // what this reading's previous should have been
            $prevDate = null;

            if ($chain) {
                $index = $chain->search(fn ($r) => $r->id === $reading->id);
                $older = ($index !== false && $index > 0) ? $chain->get($index - 1) : null;
                if ($older && (float) $older->current_reading !== (float) $reading->previous_reading) {
                    $flagged = true;
                    $expected = $older->current_reading;
                    $prevDate = $older->reading_date;
                }
            }

            $reading->is_flagged = $flagged;
            $reading->flag_expected = $expected;
            $reading->flag_prev_date = $prevDate;
        }
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return view('meter_readings.create');
    }

    /**
     * Store a new reading.
     */
    public function store(Request $request)
    {
        $data = $this->validateReading($request);

        if ($this->readingExists($data['customer_id'], $data['reading_date'])) {
            return back()->withInput()->withErrors([
                'reading_date' => 'This customer already has a reading for the selected month.',
            ]);
        }

        $previous = (float) $data['previous_reading'];

        if ((float) $data['current_reading'] < $previous) {
            return back()->withInput()->withErrors([
                'current_reading' => "Current reading must be greater than or equal to the previous reading ({$previous}).",
            ]);
        }

        $data['consumed_units'] = (float) $data['current_reading'] - $previous;
        $data['status'] = MeterReading::STATUS_PENDING;
        $data['source'] = MeterReading::SOURCE_MANUAL;

        if ($request->hasFile('photo')) {
            $data['photo'] = app(ImageService::class)->storeAsWebp($request->file('photo'), 'meter_readings');
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        MeterReading::create($data);

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading added successfully!');
    }

    /**
     * Column headers for the meter reading CSV import/template.
     */
    protected const IMPORT_COLUMNS = [
        'meter_number', 'previous_units', 'current_units', 'reading_date',
    ];

    /**
     * Download a CSV template with the expected headers and a few sample rows.
     */
    public function importTemplate()
    {
        $headers = self::IMPORT_COLUMNS;

        // Seed the template with real meter numbers when there are customers to show.
        $meters = Customer::query()
            ->whereNotNull('meter_number')
            ->where('meter_number', '!=', '')
            ->orderBy('serial_no')
            ->limit(5)
            ->pluck('meter_number')
            ->all();

        if (! $meters) {
            $meters = ['MTR-2001', 'MTR-2002', 'MTR-2003', 'MTR-2004', 'MTR-2005'];
        }

        $date = now()->startOfMonth()->toDateString();
        $rows = [];
        foreach ($meters as $i => $meter) {
            $previous = 1000 + ($i * 100);
            $rows[] = [$meter, $previous, $previous + 85, $date];
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'meter-reading-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk import meter readings from an uploaded CSV. Rows are matched to a
     * customer by meter number; anything that fails is reported and skipped.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return back()->with('error', 'The CSV file is empty.');
        }
        // Strip the UTF-8 BOM (Excel adds it) so the first header key isn't "\u{FEFF}meter_number".
        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))), $header);

        // Meter number -> customer id. Meter numbers aren't unique in the schema,
        // so keep every match and reject the ambiguous ones per row.
        $metersToIds = Customer::query()
            ->whereNotNull('meter_number')
            ->where('meter_number', '!=', '')
            ->get(['id', 'meter_number'])
            ->groupBy(fn ($c) => strtolower(trim($c->meter_number)))
            ->map(fn ($group) => $group->pluck('id')->all());

        $created = 0;
        $errors = [];
        $line = 1; // header is line 1

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            // Skip fully blank lines.
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            // Blank cells become null, not '', so the "required" rules below catch them.
            $data = [];
            foreach ($header as $i => $key) {
                $value = isset($row[$i]) ? trim((string) $row[$i]) : '';
                $data[$key] = $value === '' ? null : $value;
            }

            // Accept either the "units" wording from the template or the db column names.
            $meterNumber = (string) ($data['meter_number'] ?? '');
            $previous    = $data['previous_units'] ?? $data['previous_reading'] ?? null;
            $current     = $data['current_units'] ?? $data['current_reading'] ?? null;
            $readingDate = $data['reading_date'] ?? null;

            if (! empty($readingDate)) {
                try {
                    $readingDate = Carbon::parse($readingDate)->toDateString();
                } catch (\Throwable $e) {
                    // leave as-is; validation will flag it
                }
            }

            $matches = $metersToIds[strtolower(trim($meterNumber))] ?? [];
            if (count($matches) > 1) {
                $errors[] = "Row {$line}: meter number \"{$meterNumber}\" matches " . count($matches) . ' customers.';
                continue;
            }

            $payload = [
                'meter_number'     => $meterNumber,
                'customer_id'      => $matches[0] ?? null,
                'previous_reading' => $previous,
                'current_reading'  => $current,
                'reading_date'     => $readingDate,
            ];

            $validator = Validator::make($payload, [
                'meter_number'     => 'required|string',
                'customer_id'      => 'required|exists:customers,id',
                'previous_reading' => 'required|numeric|min:0',
                'current_reading'  => 'required|numeric|min:0',
                'reading_date'     => 'required|date',
            ], [
                'customer_id.required' => "No customer found for meter number \"{$meterNumber}\".",
                'customer_id.exists'   => "No customer found for meter number \"{$meterNumber}\".",
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$line}: " . $validator->errors()->first();
                continue;
            }

            $valid = $validator->validated();

            if ((float) $valid['current_reading'] < (float) $valid['previous_reading']) {
                $errors[] = "Row {$line}: current units ({$valid['current_reading']}) is below previous units ({$valid['previous_reading']}).";
                continue;
            }

            // Catches both an existing reading and a duplicate earlier in this file,
            // since each accepted row is saved before the next one is checked.
            if ($this->readingExists($valid['customer_id'], $valid['reading_date'])) {
                $errors[] = "Row {$line}: meter \"{$meterNumber}\" already has a reading for that month.";
                continue;
            }

            MeterReading::create([
                'customer_id'      => $valid['customer_id'],
                'previous_reading' => $valid['previous_reading'],
                'current_reading'  => $valid['current_reading'],
                'consumed_units'   => (float) $valid['current_reading'] - (float) $valid['previous_reading'],
                'reading_date'     => $valid['reading_date'],
                'status'           => MeterReading::STATUS_PENDING,
                'source'           => MeterReading::SOURCE_CSV,
                'created_by'       => auth()->id(),
                'updated_by'       => auth()->id(),
            ]);
            $created++;
        }

        fclose($handle);

        $message = "Imported {$created} meter reading(s).";
        if ($errors) {
            $message .= ' ' . count($errors) . ' row(s) skipped.';
            session()->flash('import_errors', array_slice($errors, 0, 15));
        }

        return redirect()->route('meter-readings.index')
            ->with($created ? 'success' : 'error', $message);
    }

    /**
     * Show a single reading.
     */
    public function show(MeterReading $meterReading)
    {
        $meterReading->load(['customer.sheet', 'createdBy', 'updatedBy']);

        // All previous readings for the same customer (older than this one).
        $history = MeterReading::query()
            ->where('customer_id', $meterReading->customer_id)
            ->where(function ($q) use ($meterReading) {
                $q->where('reading_date', '<', $meterReading->reading_date)
                    ->orWhere(function ($q2) use ($meterReading) {
                        $q2->where('reading_date', $meterReading->reading_date)
                            ->where('id', '<', $meterReading->id);
                    });
            })
            ->with('createdBy')
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->get();

        return view('meter_readings.show', compact('meterReading', 'history'));
    }

    /**
     * Show the edit form.
     */
    public function edit(MeterReading $meterReading)
    {
        if (! $meterReading->isPending()) {
            return redirect()->route('meter-readings.index')
                ->with('error', 'Only pending readings can be edited.');
        }

        $meterReading->load('customer');

        return view('meter_readings.edit', [
            'meterReading' => $meterReading,
        ]);
    }

    /**
     * Update a reading.
     */
    public function update(Request $request, MeterReading $meterReading)
    {
        if (! $meterReading->isPending()) {
            return redirect()->route('meter-readings.index')
                ->with('error', 'Only pending readings can be edited.');
        }

        $data = $this->validateReading($request);

        if ($this->readingExists($data['customer_id'], $data['reading_date'], $meterReading->id)) {
            return back()->withInput()->withErrors([
                'reading_date' => 'This customer already has a reading for the selected month.',
            ]);
        }

        $previous = (float) $data['previous_reading'];

        if ((float) $data['current_reading'] < $previous) {
            return back()->withInput()->withErrors([
                'current_reading' => "Current reading must be greater than or equal to the previous reading ({$previous}).",
            ]);
        }

        $data['consumed_units'] = (float) $data['current_reading'] - $previous;

        if ($request->hasFile('photo')) {
            if ($meterReading->photo) {
                Storage::disk('public')->delete($meterReading->photo);
            }
            $data['photo'] = app(ImageService::class)->storeAsWebp($request->file('photo'), 'meter_readings');
        }

        $data['updated_by'] = auth()->id();

        $meterReading->update($data);

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading updated successfully!');
    }

    /**
     * Soft delete a reading.
     */
    public function destroy(MeterReading $meterReading)
    {
        $meterReading->delete();

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading deleted successfully!');
    }

    /**
     * Shared validation rules.
     */
    protected function validateReading(Request $request): array
    {
        return $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'previous_reading' => 'required|numeric|min:0',
            'current_reading'  => 'required|numeric|min:0',
            'reading_date'     => 'required|date',
            'photo'            => 'nullable|image|max:2048',
        ], [
            'photo.max' => 'The meter Photo must not be greater than 2 mb.',
        ]);
    }

    /**
     * Whether the customer already has a reading in the same month.
     * Pass $ignoreId to exclude the record being updated.
     */
    protected function readingExists(int $customerId, string $readingDate, ?int $ignoreId = null): bool
    {
        $date = \Illuminate\Support\Carbon::parse($readingDate);

        return MeterReading::query()
            ->where('customer_id', $customerId)
            ->whereYear('reading_date', $date->year)
            ->whereMonth('reading_date', $date->month)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
