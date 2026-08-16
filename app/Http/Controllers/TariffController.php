<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Tariff;
use App\Models\TariffLog;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    /**
     * Show the per-unit-rate settings for every connection type.
     */
    public function index(Request $request)
    {
        // Ensure a row exists for each connection type.
        $tariffs = collect(Customer::CONNECTION_TYPES)->map(function ($type) {
            return Tariff::firstOrCreate(
                ['connection_type' => $type],
                [
                    'per_unit_rate'  => 0,
                    'line_charge'    => 0,
                    'service_charge' => 0,
                    'demand_charge'  => 0,
                    'created_by'     => auth()->id(),
                    'updated_by'     => auth()->id(),
                ],
            );
        });

        $logs = TariffLog::with('changedBy')
            ->when($request->filled('connection_type'), function ($q) use ($request) {
                $q->where('connection_type', $request->input('connection_type'));
            })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->input('from_date'));
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->input('to_date'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tariffs.index', [
            'tariffs'         => $tariffs,
            'logs'            => $logs,
            'connectionTypes' => Customer::CONNECTION_TYPES,
        ]);
    }

    /**
     * Save the per-unit-rate for each connection type.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'rates'             => 'required|array',
            'rates.*'           => 'required|numeric|min:0',
            'line_charges'      => 'nullable|array',
            'line_charges.*'    => 'nullable|numeric|min:0',
            'service_charges'   => 'nullable|array',
            'service_charges.*' => 'nullable|numeric|min:0',
            'demand_charges'    => 'nullable|array',
            'demand_charges.*'  => 'nullable|numeric|min:0',
        ]);

        foreach ($data['rates'] as $type => $rate) {
            if (! in_array($type, Customer::CONNECTION_TYPES, true)) {
                continue;
            }

            $tariff = Tariff::firstOrNew(['connection_type' => $type]);

            $old = [
                'per_unit_rate'  => (float) ($tariff->per_unit_rate ?? 0),
                'line_charge'    => (float) ($tariff->line_charge ?? 0),
                'service_charge' => (float) ($tariff->service_charge ?? 0),
                'demand_charge'  => (float) ($tariff->demand_charge ?? 0),
            ];

            $new = [
                'per_unit_rate'  => (float) $rate,
                'line_charge'    => (float) ($data['line_charges'][$type] ?? 0),
                'service_charge' => (float) ($data['service_charges'][$type] ?? 0),
                'demand_charge'  => (float) ($data['demand_charges'][$type] ?? 0),
            ];

            // Log only when a value actually changed on an existing tariff.
            $changed = $tariff->exists && $old !== $new;

            $tariff->fill($new + [
                'status'     => Tariff::STATUS_ACTIVE,
                'updated_by' => auth()->id(),
            ]);

            if (! $tariff->exists) {
                $tariff->created_by = auth()->id();
            }

            $tariff->save();

            if ($changed) {
                TariffLog::create([
                    'tariff_id'          => $tariff->id,
                    'connection_type'    => $type,
                    'old_rate'           => $old['per_unit_rate'],
                    'new_rate'           => $new['per_unit_rate'],
                    'old_line_charge'    => $old['line_charge'],
                    'new_line_charge'    => $new['line_charge'],
                    'old_service_charge' => $old['service_charge'],
                    'new_service_charge' => $new['service_charge'],
                    'old_demand_charge'  => $old['demand_charge'],
                    'new_demand_charge'  => $new['demand_charge'],
                    'changed_by'         => auth()->id(),
                ]);
            }
        }

        return redirect()->route('tariffs.index')
            ->with('success', 'Per unit rates updated successfully!');
    }
}
