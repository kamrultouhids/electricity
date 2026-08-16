<?php

namespace App\Services;

use App\Models\Bill;

/**
 * Single source of truth for bill money math.
 */
class BillCalculator
{
    /** Minimum charge (floor) by connection type. */
    public const MIN_CHARGE_COMMERCIAL = 350;
    public const MIN_CHARGE_DEFAULT = 300; // residential, religious, others

    /** Penalty on previous outstanding (residential). */
    public const OUTSTANDING_FLAT_LIMIT = 999;
    public const OUTSTANDING_FLAT_FEE = 100;
    public const OUTSTANDING_PERCENT = 0.10;

    /**
     * Compute the derived amounts for a bill from its raw inputs.
     *
     * @return array{energy_charge: float, late_fee: float, electricity_duty: float, total_amount: float, due_amount: float, status: int}
     */
    public function compute(array $input): array
    {
        $type      = $input['connection_type'] ?? null;
        $units     = (float) ($input['units'] ?? 0);
        $rate      = (float) ($input['per_unit_rate'] ?? 0);
        $line      = (float) ($input['line_charge'] ?? 0);
        $service   = (float) ($input['service_charge'] ?? 0);
        $demand    = (float) ($input['demand_charge'] ?? 0);
        $dutyRate  = (float) ($input['electricity_duty_rate'] ?? 0);
        $fixed     = (float) ($input['fixed_charge'] ?? 0);
        $meterRent = (float) ($input['meter_rent'] ?? 0);
        $previous  = (float) ($input['previous_outstanding'] ?? 0);
        $paid      = (float) ($input['paid_amount'] ?? 0);

        $energyCharge = $this->energyCharge($type, $units, $rate);
        $lateFee = $this->lateFee($type, $previous);
        $duty = $this->electricityDuty($energyCharge, $lateFee, $dutyRate);

        $total = round($energyCharge + $line + $service + $demand + $duty + $fixed + $meterRent + $previous + $lateFee, 2);
        $due = round($total - $paid, 2);

        return [
            'energy_charge'    => $energyCharge,
            'late_fee'         => $lateFee,
            'electricity_duty' => $duty,
            'total_amount'     => $total,
            'due_amount'       => $due,
            'status'           => $this->status($total, $paid),
        ];
    }

    /**
     * Electricity duty — a percentage of the energy charge.
     */
    public function electricityDuty(float $energyCharge, float $lateFee, float $dutyRate): float
    {
        if ($dutyRate <= 0) {
            return 0.0;
        }
        return round($energyCharge * $dutyRate / 100, 2);
    }

    /**
     * Energy charge: every unit is billed at the rate (units * rate), with the
     * connection-type minimum charge acting as a floor.
     */
    public function energyCharge(?string $type, float $units, float $rate): float
    {
        $minimum = $this->minimumCharge($type);
        $metered = max(0.0, $units) * $rate;

        return round(max($minimum, $metered), 2);
    }

    /**
     * Flat minimum charge for the connection type.
     */
    public function minimumCharge(?string $type): float
    {
        return $type === 'commercial'
            ? (float) self::MIN_CHARGE_COMMERCIAL
            : (float) self::MIN_CHARGE_DEFAULT;
    }

    /**
     * Penalty on previous outstanding (same for all connection types).
     * 1..999 -> flat 100, above 999 -> 10%.
     */
    public function lateFee(?string $type, float $previousOutstanding): float
    {
        if ($previousOutstanding <= 0) {
            return 0.0;
        }

        if ($previousOutstanding <= self::OUTSTANDING_FLAT_LIMIT) {
            return (float) self::OUTSTANDING_FLAT_FEE;
        }

        return round($previousOutstanding * self::OUTSTANDING_PERCENT, 2);
    }

    /**
     * Resolve the payment status from total vs paid.
     */
    public function status(float $total, float $paid): int
    {
        if ($paid <= 0) {
            return Bill::STATUS_UNPAID;
        }

        if ($paid < $total) {
            return Bill::STATUS_PARTIAL;
        }

        return Bill::STATUS_PAID;
    }
}
