<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\MeterReading;
use App\Models\Tariff;
use Illuminate\Support\Carbon;

/**
 * Bill generation from meter readings.
 */
class BillGenerator
{
    public function __construct(private BillCalculator $calculator)
    {
    }

    /**
     * Build the (unsaved) bill data for a reading — used for the preview and the save.
     */
    public function buildData(MeterReading $reading): array
    {
        $customer = $reading->customer;
        $billingMonth = Carbon::parse($reading->reading_date)->startOfMonth()->toDateString();

        // Snapshot the connection type's tariff onto the bill so later tariff
        // edits never change an already generated bill.
        $tariff = Tariff::resolveFor($customer->connection_type);

        $rate          = (float) ($tariff?->per_unit_rate ?? 0);
        $lineCharge    = (float) ($tariff?->line_charge ?? 0);
        $serviceCharge = (float) ($tariff?->service_charge ?? 0);
        $demandCharge  = (float) ($tariff?->demand_charge ?? 0);
        $dutyRate      = (float) ($tariff?->electricity_duty ?? 0);

        // Carry forward the latest prior bill's due (it already rolls up earlier months).
        $previousOutstanding = (float) (Bill::query()
            ->where('customer_id', $customer->id)
            ->whereDate('billing_month', '<', $billingMonth)
            ->orderByDesc('billing_month')
            ->value('due_amount') ?? 0);

        $computed = $this->calculator->compute([
            'connection_type'      => $customer->connection_type,
            'units'                => $reading->consumed_units,
            'per_unit_rate'        => $rate,
            'line_charge'          => $lineCharge,
            'service_charge'       => $serviceCharge,
            'demand_charge'        => $demandCharge,
            'electricity_duty_rate' => $dutyRate,
            'previous_outstanding' => $previousOutstanding,
        ]);

        return [
            'customer_id'          => $customer->id,
            'meter_reading_id'     => $reading->id,
            'billing_month'        => $billingMonth,
            'units'                => (float) $reading->consumed_units,
            'per_unit_rate'        => $rate,
            'energy_charge'        => $computed['energy_charge'],
            'line_charge'          => $lineCharge,
            'service_charge'       => $serviceCharge,
            'demand_charge'        => $demandCharge,
            'electricity_duty_rate' => $dutyRate,
            'electricity_duty'     => $computed['electricity_duty'],
            'fixed_charge'         => 0,
            'meter_rent'           => 0,
            'previous_outstanding' => $previousOutstanding,
            'late_fee'             => $computed['late_fee'],
            'total_amount'         => $computed['total_amount'],
            'paid_amount'          => 0,
            'due_amount'           => $computed['due_amount'],
            'previous_bills_snapshot' => $this->previousBillsSnapshot($customer->id, $billingMonth),
            'status'               => $computed['status'],
        ];
    }

    /**
     * Freeze the previous months as they stand right now, so reprinting an old
     * bill later shows the same history as the copy handed to the customer
     * (payments made afterwards would otherwise rewrite those figures).
     */
    protected function previousBillsSnapshot(int $customerId, string $billingMonth): array
    {
        return Bill::query()
            ->where('customer_id', $customerId)
            ->whereDate('billing_month', '<', $billingMonth)
            ->orderByDesc('billing_month')
            ->limit(Bill::HISTORY_MONTHS)
            ->get(['billing_month', 'units', 'total_amount', 'paid_amount', 'discount', 'due_amount'])
            ->map(fn (Bill $bill) => [
                'billing_month' => $bill->billing_month->toDateString(),
                'units'         => (float) $bill->units,
                'total_amount'  => (float) $bill->total_amount,
                'paid_amount'   => (float) $bill->paid_amount,
                'discount'      => (float) $bill->discount,
                'due_amount'    => (float) $bill->due_amount,
            ])
            ->all();
    }

    /**
     * Whether the reading's customer already has a bill for that month.
     */
    public function alreadyBilled(MeterReading $reading): bool
    {
        $billingMonth = Carbon::parse($reading->reading_date)->startOfMonth()->toDateString();

        return Bill::query()
            ->where('customer_id', $reading->customer_id)
            ->whereDate('billing_month', $billingMonth)
            ->exists();
    }

    /**
     * Generate the bill for a single reading and mark the reading Completed.
     * Returns null when it is not billable (inactive customer / already billed).
     */
    public function generateForReading(MeterReading $reading, ?int $userId = null): ?Bill
    {
        $customer = $reading->customer;

        if (! $customer || ! $customer->isActive() || $this->alreadyBilled($reading)) {
            return null;
        }

        $bill = Bill::create($this->buildData($reading) + [
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $reading->update([
            'status'     => MeterReading::STATUS_COMPLETED,
            'updated_by' => $userId,
        ]);

        return $bill;
    }

    /**
     * Bulk generate for every pending reading in a month (used by the artisan command).
     *
     * @return array{generated: int, skipped: int}
     */
    public function generateForMonth(string $month, ?int $sheetId = null, ?int $userId = null): array
    {
        $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $readings = MeterReading::query()
            ->with('customer')
            ->where('status', MeterReading::STATUS_PENDING)
            ->whereYear('reading_date', $date->year)
            ->whereMonth('reading_date', $date->month)
            ->when($sheetId, function ($q) use ($sheetId) {
                $q->whereHas('customer', fn ($c) => $c->where('sheet_id', $sheetId));
            })
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($readings as $reading) {
            $this->generateForReading($reading, $userId) ? $generated++ : $skipped++;
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }
}
