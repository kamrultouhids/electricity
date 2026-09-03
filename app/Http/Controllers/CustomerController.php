<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sheet;
use Illuminate\Http\Request;
use App\Services\CustomerOpeningBalance;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class CustomerController extends Controller
{
    /**
     * List customers with search and filters.
     */
    public function index(Request $request)
    {
        $query = Customer::query()->with('sheet');

        // Search by serial_no, name, mobile_number, or meter_number
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%");
            });
        }

        // Filter by connection type
        if ($request->filled('connection_type')) {
            $query->where('connection_type', $request->input('connection_type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        // Filter by sheet
        if ($request->filled('sheet_id')) {
            $query->where('sheet_id', (int) $request->input('sheet_id'));
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        return view('customers.index', [
            'customers'      => $customers,
            'connectionTypes' => Customer::CONNECTION_TYPES,
            'sheets'         => Sheet::orderBy('id')->get(),
        ]);
    }

    /**
     * JSON customer search for remote (Tom Select) dropdowns.
     * Returns at most 20 matches so large customer bases stay responsive.
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->input('q'));

        $customers = Customer::query()
            ->with('latestMeterReading')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('mobile_number', 'like', "%{$term}%")
                        ->orWhere('meter_number', 'like', "%{$term}%")
                        ->orWhere('serial_no', 'like', "%{$term}%");
                });
            })
            ->where('status', Customer::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Customer $c) => [
                'id'   => $c->id,
                'text' => $c->name.' ('.$c->meter_number.')',
                'last' => (float) ($c->latestMeterReading->current_reading ?? 0),
            ]);

        return response()->json($customers);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return view('customers.create', [
            'connectionTypes' => Customer::CONNECTION_TYPES,
            'sheets'          => Sheet::orderBy('id')->get(),
        ]);
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request, CustomerOpeningBalance $opening)
    {
        $data = $this->validateCustomer($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = app(ImageService::class)->storeAsWebp($request->file('photo'), 'customers');
        }

        $data['source'] = Customer::SOURCE_MANUAL;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $customer = Customer::create($data);

        // An existing customer arrives mid-history: anchor their meter and open
        // their ledger with what they already owe.
        $opening->materialize($customer, auth()->id());

        return redirect()->route('customers.index')
            ->with('success', 'Customer added successfully!');
    }

    /**
     * Column headers for the customer CSV import/template.
     */
    protected const IMPORT_COLUMNS = [
        'sheet', 'serial_no', 'name', 'father_or_husband_name', 'mother_name',
        'mobile_number', 'address', 'educational_qualification', 'age', 'occupation',
        'religion', 'national_id', 'guardian_name', 'guardian_relationship', 'guardian_address',
        'meter_number', 'connection_type', 'connection_date', 'status',
        'opening_reading', 'opening_due', 'opening_as_of',
    ];

    /**
     * Download a blank CSV template with the expected headers.
     */
    public function importTemplate()
    {
        $headers = self::IMPORT_COLUMNS;

        // Sample rows (also serves as a ready-to-edit demo dataset).
        $rows = [
            ['Sheet 1', '2001', 'Kamrul Islam', 'Abdul Karim', 'Rahima Begum', '01710000001', 'Jaforabad Chittagong', 'Graduate', '38', 'Business', 'Islam', '1990010101', '', '', '', 'MTR-2001', 'residential', '2025-01-10', 'active', '4820', '1250.00', '2026-08-31'],
            ['Sheet 2', '2002', 'Raju', 'Hasan Ali', 'Salma Khatun', '01710000002', 'Sitakunda Chittagong', 'HSC', '29', 'Driver', 'Islam', '1990010102', '', '', '', 'MTR-2002', 'residential', '2025-02-15', 'active', '1160', '0', '2026-08-31'],
            ['Sheet 3', '2003', 'Sakib', 'Jamal Uddin', 'Nasima Akter', '01710000003', 'Jangal Salimpur', 'SSC', '26', 'Shopkeeper', 'Islam', '1990010103', '', '', '', 'MTR-2003', 'commercial', '2025-03-05', 'active', '', '', ''],
            ['Sheet 4', '2004', 'Tamim', 'Sultan Mahmud', 'Ferdousi Begum', '01710000004', 'Jaforabad Chittagong', 'Graduate', '31', 'Service', 'Islam', '1990010104', '', '', '', 'MTR-2004', 'residential', '2025-03-20', 'active', '', '', ''],
            ['Sheet 5', '2005', 'Musfiq', 'Anwar Hossain', 'Rehana Parvin', '01710000005', 'Sitakunda Chittagong', 'Masters', '34', 'Teacher', 'Islam', '1990010105', '', '', '', 'MTR-2005', 'residential', '2025-04-12', 'active', '', '', ''],
            ['Sheet 6', '2006', 'Lionel Messi', 'Jorge Messi', 'Celia Cuccittini', '01710000006', 'Rosario Colony', 'Graduate', '37', 'Athlete', 'Christianity', '1990010106', '', '', '', 'MTR-2006', 'commercial', '2025-05-01', 'active', '', '', ''],
            ['Sheet 7', '2007', 'Cristiano Ronaldo', 'Dinis Aveiro', 'Dolores Aveiro', '01710000007', 'Madeira Block', 'Graduate', '40', 'Athlete', 'Christianity', '1990010107', '', '', '', 'MTR-2007', 'residential', '2025-05-18', 'active', '', '', ''],
            ['Sheet 8', '2008', 'Erling Haaland', 'Alfie Haaland', 'Gry Marita', '01710000008', 'Leeds Lane', 'HSC', '24', 'Athlete', 'Christianity', '1990010108', '', '', '', 'MTR-2008', 'residential', '2025-06-09', 'active', '', '', ''],
            ['Sheet 9', '2009', 'Mohamed Salah', 'Salah Ghaly', 'Naglaa Salah', '01710000009', 'Nagrig Street', 'Graduate', '33', 'Athlete', 'Islam', '1990010109', '', '', '', 'MTR-2009', 'commercial', '2025-07-22', 'active', '', '', ''],
            ['Sheet 10', '2010', 'Kevin De Bruyne', 'Herwig De Bruyne', 'Anna De Bruyne', '01710000010', 'Drongen Road', 'Graduate', '34', 'Athlete', 'Christianity', '1990010110', '', '', '', 'MTR-2010', 'residential', '2025-08-03', 'inactive', '', '', ''],
            ['Sheet 11', '2011', 'Neymar Jr', 'Neymar Sr', 'Nadine Santos', '01710000011', 'Mogi das Cruzes', 'HSC', '32', 'Athlete', 'Christianity', '1990010111', '', '', '', 'MTR-2011', 'residential', '2025-09-14', 'active', '', '', ''],
        ];

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'customer-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk import customers from an uploaded CSV.
     */
    public function import(Request $request, CustomerOpeningBalance $opening)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        // Header row -> normalised column keys.
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return back()->with('error', 'The CSV file is empty.');
        }
        // Strip the UTF-8 BOM (Excel adds it) so the first header key isn't "\u{FEFF}sheet".
        $header = array_map(fn ($h) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $h))), $header);

        // Resolve sheet names -> ids once.
        $sheets = Sheet::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [trim($name) => $id])->toArray();
        $created = 0;
        $errors = [];
        $line = 1; // header is line 1

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            // Skip fully blank lines.
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            // Blank cells become null, not '' — an empty string slips past a
            // "nullable" rule and then fails on a non-string column (e.g. age).
            $data = [];
            foreach ($header as $i => $key) {
                $value = isset($row[$i]) ? trim((string) $row[$i]) : '';
                $data[$key] = $value === '' ? null : $value;
            }

            // Resolve sheet (accept name or numeric id) and normalise a few fields.
            $sheetRaw = trim((string) ($data['sheet'] ?? ''));
            $data['sheet_id'] = $sheets[$sheetRaw] ?? (ctype_digit($sheetRaw) ? (int) $sheetRaw : null);
            $data['status'] = $this->normaliseStatus($data['status'] ?? null);
            $data['connection_type'] = strtolower((string) ($data['connection_type'] ?? ''));
            foreach (['connection_date', 'opening_as_of'] as $dateField) {
                if (! empty($data[$dateField])) {
                    try {
                        $data[$dateField] = Carbon::parse($data[$dateField])->toDateString();
                    } catch (\Throwable $e) {
                        // leave as-is; validation will flag it
                    }
                }
            }

            $validator = Validator::make($data, [
                'sheet_id'        => 'required|exists:sheets,id',
                'serial_no'       => 'required|string',
                'name'            => 'required|string',
                'mobile_number'   => 'required|string|max:20',
                'address'         => 'required|string',
                'age'             => 'nullable|integer|min:0|max:150',
                'meter_number'    => 'required|string',
                'connection_type' => 'required|in:' . implode(',', Customer::CONNECTION_TYPES),
                'connection_date' => 'required|date',
                // Same all-or-nothing rule as the form: a row either declares a
                // full opening balance or none at all.
                'opening_as_of'   => 'nullable|date|after_or_equal:connection_date|before_or_equal:today',
                'opening_reading' => 'nullable|numeric|min:0|required_with:opening_as_of',
                'opening_due'     => 'nullable|numeric|min:0|required_with:opening_as_of',
            ], [
                'opening_as_of.after_or_equal'   => 'opening_as_of cannot be before the connection date.',
                'opening_as_of.before_or_equal'  => 'opening_as_of cannot be in the future.',
                'opening_reading.required_with'  => 'opening_reading is required when opening_as_of is set.',
                'opening_due.required_with'      => 'opening_due is required when opening_as_of is set.',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$line}: " . $validator->errors()->first();
                continue;
            }

            $valid = $validator->validated();
            // Carry over the optional text fields not in the validator.
            foreach (['father_or_husband_name', 'mother_name', 'educational_qualification',
                      'occupation', 'religion', 'national_id', 'guardian_name',
                      'guardian_relationship', 'guardian_address'] as $opt) {
                $valid[$opt] = $data[$opt] ?? null;
            }
            $valid['source'] = Customer::SOURCE_CSV;
            $valid['created_by'] = auth()->id();
            $valid['updated_by'] = auth()->id();

            $opening->materialize(Customer::create($valid), auth()->id());
            $created++;
        }

        fclose($handle);

        $message = "Imported {$created} customer(s).";
        if ($errors) {
            $message .= ' ' . count($errors) . ' row(s) skipped.';
            session()->flash('import_errors', array_slice($errors, 0, 15));
        }

        return redirect()->route('customers.index')
            ->with($created ? 'success' : 'error', $message);
    }

    /**
     * Normalise a status cell (accepts 1/0, active/inactive) to 1/0.
     */
    protected function normaliseStatus($value): ?int
    {
        $v = strtolower(trim((string) $value));
        return match ($v) {
            '1', 'active'   => 1,
            '0', 'inactive' => 0,
            default         => null,
        };
    }

    /**
     * Show a single customer.
     */
    public function show(Customer $customer)
    {
        $customer->load(['payments.collector']);

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Customer $customer, CustomerOpeningBalance $opening)
    {
        return view('customers.edit', [
            'customer'        => $customer,
            'connectionTypes' => Customer::CONNECTION_TYPES,
            'sheets'          => Sheet::orderBy('id')->get(),
            'openingBlocked'  => $opening->blockedReason($customer),
        ]);
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, Customer $customer, CustomerOpeningBalance $opening)
    {
        $data = $this->validateCustomer($request, $customer->id);

        $openingChanged = $this->openingChanged($customer, $data);

        // Once a bill has been raised on top of the opening balance or money has
        // been taken against it, those figures are history — a later bill froze
        // them into its carried balance and its printed copy. Correcting them
        // then has to be a new entry, not a rewrite of the opening.
        if ($blocked = $opening->blockedReason($customer)) {
            if ($openingChanged) {
                return back()->withInput()->withErrors(['opening_due' => $blocked]);
            }
            unset($data['opening_reading'], $data['opening_due'], $data['opening_as_of']);
        }

        if ($request->hasFile('photo')) {
            if ($customer->photo) {
                Storage::disk('public')->delete($customer->photo);
            }
            $data['photo'] = app(ImageService::class)->storeAsWebp($request->file('photo'), 'customers');
        }

        $data['updated_by'] = auth()->id();

        $customer->update($data);

        if ($openingChanged) {
            $opening->adjust($customer->refresh(), auth()->id());
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully!');
    }

    /**
     * Whether the submitted opening figures differ from what is on record.
     * Compared loosely — the form round-trips them as strings.
     */
    protected function openingChanged(Customer $customer, array $data): bool
    {
        $asOf = $data['opening_as_of'] ?? null;
        $currentAsOf = $customer->opening_as_of?->toDateString();

        if (($asOf ? Carbon::parse($asOf)->toDateString() : null) !== $currentAsOf) {
            return true;
        }

        foreach (['opening_reading', 'opening_due'] as $field) {
            $submitted = $data[$field] ?? null;
            $current = $customer->{$field};

            if (($submitted === null) !== ($current === null)) {
                return true;
            }

            if ($submitted !== null && round((float) $submitted, 2) !== round((float) $current, 2)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Soft delete a customer.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully!');
    }

    /**
     * Toggle active / inactive status.
     */
    public function toggleStatus(Customer $customer)
    {
        $customer->update([
            'status' => $customer->isActive()
                ? Customer::STATUS_INACTIVE
                : Customer::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'Customer status updated!');
    }

    /**
     * Shared validation rules.
     */
    protected function validateCustomer(Request $request, ?int $customerId = null): array
    {
        $data = $request->validate([
            'sheet_id'                  => 'required|exists:sheets,id',
            'serial_no'                 => 'required|string',
            'photo'                     => 'nullable|image|max:2048',
            'name'                      => 'required|string',
            'father_or_husband_name'    => 'nullable|string',
            'mother_name'               => 'nullable|string',
            'mobile_number'             => 'required|string|max:20',
            'address'                   => 'required|string',
            'educational_qualification' => 'nullable|string',
            'age'                       => 'nullable|integer|min:0|max:150',
            'occupation'                => 'nullable|string',
            'religion'                  => 'nullable|string',
            'national_id'               => 'nullable|string|max:255',
            'guardian_name'             => 'nullable|string',
            'guardian_relationship'     => 'nullable|string',
            'guardian_address'          => 'nullable|string',
            'meter_number'              => 'required|string',
            'connection_type'           => 'required|in:' . implode(',', Customer::CONNECTION_TYPES),
            'connection_date'           => 'required|date',
            // Opening balances — all or nothing, and only for a customer who
            // predates the system. A zero due with a reading is valid: the
            // customer is paid up but the meter still has to start somewhere.
            'opening_as_of'             => 'nullable|date|after_or_equal:connection_date|before_or_equal:today',
            'opening_reading'           => 'nullable|numeric|min:0|required_with:opening_as_of',
            'opening_due'               => 'nullable|numeric|min:0|required_with:opening_as_of',
            'status'                    => 'required|in:0,1',
        ], [
            'photo.max' => 'The photo field must not be greater than 2 mb.',
            'opening_as_of.after_or_equal' => 'The opening balance date cannot be before the connection date.',
            'opening_as_of.before_or_equal' => 'The opening balance date cannot be in the future.',
            'opening_reading.required_with' => 'Enter the meter reading the customer was brought over at.',
            'opening_due.required_with' => 'Enter the outstanding amount carried over (0 if they are paid up).',
        ]);

        // An unticked "existing customer" box submits nothing; make that an
        // explicit clear so unsetting it removes the declared figures too.
        foreach (['opening_as_of', 'opening_reading', 'opening_due'] as $field) {
            $data[$field] = $data[$field] ?? null;
        }

        return $data;
    }
}
