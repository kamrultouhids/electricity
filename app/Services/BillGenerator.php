<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillRevision;
use App\Models\MeterReading;
use App\Models\Tariff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $previousBill = $this->previousBill($customer->id, $billingMonth);
        $previousOutstanding = (float) ($previousBill?->due_amount ?? 0);
// dd($previousOutstanding,$previousBill->toArray());
        $computed = $this->calculator->compute([
            'connection_type'      => $customer->connection_type,
            'units'                => $reading->consumed_units,
            'per_unit_rate'        => $rate,
            'line_charge'          => $lineCharge,
            'service_charge'       => $serviceCharge,
            'demand_charge'        => $demandCharge,
            'electricity_duty_rate' => $dutyRate,
            'previous_outstanding' => $previousOutstanding,
            'late_fee_basis'       => $this->lateFeeBasis($previousOutstanding, $previousBill),
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
     * Recalculate a bill from a corrected current reading.
     *
     * The bill's own tariff snapshot, carried balance and frozen history are
     * kept — only what the reading drives is recomputed, so a units correction
     * can never smuggle in a later tariff change or re-derive a carried balance
     * that has since been settled.
     *
     * @return array{units: float, energy_charge: float, electricity_duty: float, total_amount: float, due_amount: float, status: int}
     */
    public function reviseData(Bill $bill, float $currentReading, float $previousReading): array
    {
        $units = round($currentReading - $previousReading, 2);

        $computed = $this->calculator->compute([
            'connection_type'       => $bill->customer->connection_type,
            'units'                 => $units,
            'per_unit_rate'         => (float) $bill->per_unit_rate,
            'line_charge'           => (float) $bill->line_charge,
            'service_charge'        => (float) $bill->service_charge,
            'demand_charge'         => (float) $bill->demand_charge,
            'electricity_duty_rate' => (float) $bill->electricity_duty_rate,
            'fixed_charge'          => (float) $bill->fixed_charge,
            'meter_rent'            => (float) $bill->meter_rent,
            // Preserved, so compute() reproduces the same late fee it derived
            // from it when the bill was issued — including the suppression when
            // the balance was carried in from the old system.
            'previous_outstanding'  => (float) $bill->previous_outstanding,
            'late_fee_basis'        => $this->lateFeeBasis(
                (float) $bill->previous_outstanding,
                $this->previousBill($bill->customer_id, $bill->billing_month->toDateString()),
            ),
            'paid_amount'           => (float) $bill->paid_amount,
        ]);

        return [
            'units'            => $units,
            'energy_charge'    => $computed['energy_charge'],
            'electricity_duty' => $computed['electricity_duty'],
            'late_fee'         => $computed['late_fee'],
            'total_amount'     => $computed['total_amount'],
            'due_amount'       => $computed['due_amount'],
            'status'           => $computed['status'],
        ];
    }

    /**
     * Apply a corrected reading to its bill and log the revision.
     */
    public function revise(Bill $bill, float $currentReading, string $reason, ?int $userId = null): BillRevision
    {
        return DB::transaction(function () use ($bill, $currentReading, $reason, $userId) {
            $bill = Bill::query()->with('customer', 'meterReading')->lockForUpdate()->findOrFail($bill->id);
            $reading = $bill->meterReading;

            $revised = $this->reviseData($bill, $currentReading, (float) $reading->previous_reading);

            $revision = BillRevision::create([
                'bill_id'             => $bill->id,
                'meter_reading_id'    => $reading->id,
                'old_current_reading' => (float) $reading->current_reading,
                'new_current_reading' => $currentReading,
                'old_units'           => (float) $bill->units,
                'new_units'           => $revised['units'],
                'old_total_amount'    => (float) $bill->total_amount,
                'new_total_amount'    => $revised['total_amount'],
                'old_due_amount'      => (float) $bill->due_amount,
                'new_due_amount'      => $revised['due_amount'],
                'reason'              => $reason,
                'changed_by'          => $userId,
            ]);

            $reading->update([
                'current_reading' => $currentReading,
                'consumed_units'  => $revised['units'],
                'updated_by'      => $userId,
            ]);

            $bill->update([
                'units'            => $revised['units'],
                'energy_charge'    => $revised['energy_charge'],
                'electricity_duty' => $revised['electricity_duty'],
                'late_fee'         => $revised['late_fee'],
                'total_amount'     => $revised['total_amount'],
                'due_amount'       => $revised['due_amount'],
                'status'           => $revised['status'],
                'updated_by'       => $userId,
            ]);

            return $revision;
        });
    }

    /**
     * The customer's most recent bill before the given month — the one whose
     * due rolls forward into it.
     */
    protected function previousBill(int $customerId, string $billingMonth): ?Bill
    {
        return Bill::query()
            ->where('customer_id', $customerId)
            ->whereDate('billing_month', '<', $billingMonth)
            ->orderByDesc('billing_month')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * What the late fee is charged on.
     *
     * A debt carried in from the old system is not overdue under this system's
     * terms — the customer was never issued a bill here and given a chance to
     * pay it — so the first bill after an opening balance carries the balance
     * without the penalty. From the month after that it is an ordinary unpaid
     * balance and is penalised like any other.
     */
    protected function lateFeeBasis(float $previousOutstanding, ?Bill $previousBill): float
    {
        // return $previousBill?->is_opening ? 0.0 : $previousOutstanding;
       return $previousOutstanding;

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
