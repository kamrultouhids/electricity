<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $readings = $query->latest('reading_date')->latest('id')
            ->paginate(15)->withQueryString();

        return view('meter_readings.index', [
            'readings'      => $readings,
            'sheets'        => Sheet::orderBy('name')->get(),
            'statusOptions' => MeterReading::STATUS_LABELS,
        ]);
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

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('meter_readings', 'public');
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        MeterReading::create($data);

        return redirect()->route('meter-readings.index')
            ->with('success', 'Meter reading added successfully!');
    }

    /**
     * Show a single reading.
     */
    public function show(MeterReading $meterReading)
    {
        $meterReading->load(['customer.sheet', 'createdBy', 'updatedBy']);

        return view('meter_readings.show', compact('meterReading'));
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
            $data['photo'] = $request->file('photo')->store('meter_readings', 'public');
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
