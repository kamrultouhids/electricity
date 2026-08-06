<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $data['photo'] = $request->file('photo')->store('customers', 'public');
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        Customer::create($data);

        return redirect()->route('customers.index')
            ->with('success', 'Customer added successfully!');
    }

    /**
     * Show a single customer.
     */
    public function show(Customer $customer)
    {
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
            $data['photo'] = $request->file('photo')->store('customers', 'public');
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
            'status'                    => 'required|in:0,1',
        ], [
            'photo.max' => 'The photo field must not be greater than 2 mb.',
        ]);
    }
}
