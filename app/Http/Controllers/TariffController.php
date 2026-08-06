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
                ['per_unit_rate' => 0, 'created_by' => auth()->id(), 'updated_by' => auth()->id()],
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
            'rates'               => 'required|array',
            'rates.*'             => 'required|numeric|min:0',
        ]);

        foreach ($data['rates'] as $type => $rate) {
            if (! in_array($type, Customer::CONNECTION_TYPES, true)) {
                continue;
            }

            $tariff = Tariff::firstOrNew(['connection_type' => $type]);
            $oldRate = $tariff->per_unit_rate ?? 0;

            // Log only when the rate actually changed on an existing tariff.
            $rateChanged = $tariff->exists
                && (float) $oldRate !== (float) $rate;

            $tariff->fill([
                'per_unit_rate' => $rate,
                'status'        => Tariff::STATUS_ACTIVE,
                'updated_by'    => auth()->id(),
            ]);

            if (! $tariff->exists) {
                $tariff->created_by = auth()->id();
            }

            $tariff->save();

            if ($rateChanged) {
                TariffLog::create([
                    'tariff_id'       => $tariff->id,
                    'connection_type' => $type,
                    'old_rate'        => $oldRate,
                    'new_rate'        => $rate,
                    'changed_by'      => auth()->id(),
                ]);
            }
        }

        return redirect()->route('tariffs.index')
            ->with('success', 'Per unit rates updated successfully!');
    }
}
