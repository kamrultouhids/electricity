<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sheet;
use Illuminate\Http\Request;
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
            'sheets'         => Sheet::orderBy('name')->get(),
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
            'sheets'          => Sheet::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = app(ImageService::class)->storeAsWebp($request->file('photo'), 'customers');
        }

        $data['source'] = Customer::SOURCE_MANUAL;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        Customer::create($data);

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
    ];

    /**
     * Download a blank CSV template with the expected headers.
     */
    public function importTemplate()
    {
        $headers = self::IMPORT_COLUMNS;

        // Sample rows (also serves as a ready-to-edit demo dataset).
        $rows = [
            ['Sheet 1', '2001', 'Kamrul Islam', 'Abdul Karim', 'Rahima Begum', '01710000001', 'Jaforabad Chittagong', 'Graduate', '38', 'Business', 'Islam', '1990010101', '', '', '', 'MTR-2001', 'residential', '2025-01-10', 'active'],
            ['Sheet 2', '2002', 'Raju', 'Hasan Ali', 'Salma Khatun', '01710000002', 'Sitakunda Chittagong', 'HSC', '29', 'Driver', 'Islam', '1990010102', '', '', '', 'MTR-2002', 'residential', '2025-02-15', 'active'],
            ['Sheet 3', '2003', 'Sakib', 'Jamal Uddin', 'Nasima Akter', '01710000003', 'Jangal Salimpur', 'SSC', '26', 'Shopkeeper', 'Islam', '1990010103', '', '', '', 'MTR-2003', 'commercial', '2025-03-05', 'active'],
            ['Sheet 4', '2004', 'Tamim', 'Sultan Mahmud', 'Ferdousi Begum', '01710000004', 'Jaforabad Chittagong', 'Graduate', '31', 'Service', 'Islam', '1990010104', '', '', '', 'MTR-2004', 'residential', '2025-03-20', 'active'],
            ['Sheet 5', '2005', 'Musfiq', 'Anwar Hossain', 'Rehana Parvin', '01710000005', 'Sitakunda Chittagong', 'Masters', '34', 'Teacher', 'Islam', '1990010105', '', '', '', 'MTR-2005', 'residential', '2025-04-12', 'active'],
            ['Sheet 6', '2006', 'Lionel Messi', 'Jorge Messi', 'Celia Cuccittini', '01710000006', 'Rosario Colony', 'Graduate', '37', 'Athlete', 'Christianity', '1990010106', '', '', '', 'MTR-2006', 'commercial', '2025-05-01', 'active'],
            ['Sheet 7', '2007', 'Cristiano Ronaldo', 'Dinis Aveiro', 'Dolores Aveiro', '01710000007', 'Madeira Block', 'Graduate', '40', 'Athlete', 'Christianity', '1990010107', '', '', '', 'MTR-2007', 'residential', '2025-05-18', 'active'],
            ['Sheet 8', '2008', 'Erling Haaland', 'Alfie Haaland', 'Gry Marita', '01710000008', 'Leeds Lane', 'HSC', '24', 'Athlete', 'Christianity', '1990010108', '', '', '', 'MTR-2008', 'residential', '2025-06-09', 'active'],
            ['Sheet 9', '2009', 'Mohamed Salah', 'Salah Ghaly', 'Naglaa Salah', '01710000009', 'Nagrig Street', 'Graduate', '33', 'Athlete', 'Islam', '1990010109', '', '', '', 'MTR-2009', 'commercial', '2025-07-22', 'active'],
            ['Sheet 10', '2010', 'Kevin De Bruyne', 'Herwig De Bruyne', 'Anna De Bruyne', '01710000010', 'Drongen Road', 'Graduate', '34', 'Athlete', 'Christianity', '1990010110', '', '', '', 'MTR-2010', 'residential', '2025-08-03', 'inactive'],
            ['Sheet 11', '2011', 'Neymar Jr', 'Neymar Sr', 'Nadine Santos', '01710000011', 'Mogi das Cruzes', 'HSC', '32', 'Athlete', 'Christianity', '1990010111', '', '', '', 'MTR-2011', 'residential', '2025-09-14', 'active'],
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
    public function import(Request $request)
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
            if (! empty($data['connection_date'])) {
                try {
                    $data['connection_date'] = Carbon::parse($data['connection_date'])->toDateString();
                } catch (\Throwable $e) {
                    // leave as-is; validation will flag it
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

            Customer::create($valid);
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
    public function edit(Customer $customer)
    {
        return view('customers.edit', [
            'customer'        => $customer,
            'connectionTypes' => Customer::CONNECTION_TYPES,
            'sheets'          => Sheet::orderBy('name')->get(),
        ]);
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $data = $this->validateCustomer($request, $customer->id);

        if ($request->hasFile('photo')) {
            if ($customer->photo) {
                Storage::disk('public')->delete($customer->photo);
            }
            $data['photo'] = app(ImageService::class)->storeAsWebp($request->file('photo'), 'customers');
        }

        $data['updated_by'] = auth()->id();

        $customer->update($data);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully!');
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
        return $request->validate([
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
            'status'                    => 'required|in:0,1',
        ], [
            'photo.max' => 'The photo field must not be greater than 2 mb.',
        ]);
    }
}
