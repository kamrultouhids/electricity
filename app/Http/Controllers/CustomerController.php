<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * List customers with search and filters.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search by serial_no, name, or meter_number
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
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

        $customers = $query->latest()->paginate(15)->withQueryString();

        return view('customers.index', [
            'customers'      => $customers,
            'connectionTypes' => Customer::CONNECTION_TYPES,
        ]);
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        return view('customers.create', [
            'connectionTypes' => Customer::CONNECTION_TYPES,
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
            'serial_no'                 => 'required|string',
            'photo'                     => 'nullable|image',
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
            'connection_type'           => 'nullable|in:' . implode(',', Customer::CONNECTION_TYPES),
            'status'                    => 'required|in:0,1',
        ]);
    }
}
