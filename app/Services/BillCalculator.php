<?php

namespace App\Services;

use App\Models\Bill;

/**
 * Single source of truth for bill money math.
 */
class BillCalculator
{
    /** Units covered by the flat minimum charge. */
    public const SLAB_UNITS = 25;

    /** Minimum (slab) charge for 0..SLAB_UNITS units, by connection type. */
    public const MIN_CHARGE_COMMERCIAL = 350;
    public const MIN_CHARGE_DEFAULT = 300; // residential, religious, others

    /** Penalty on previous outstanding (residential). */
    public const OUTSTANDING_FLAT_LIMIT = 999;
    public const OUTSTANDING_FLAT_FEE = 100;
    public const OUTSTANDING_PERCENT = 0.10;

    /**
     * Compute the derived amounts for a bill from its raw inputs.
     *
     * @return array{energy_charge: float, late_fee: float, total_amount: float, due_amount: float, status: int}
     */
    public function compute(array $input): array
    {
        $type      = $input['connection_type'] ?? null;
        $units     = (float) ($input['units'] ?? 0);
        $rate      = (float) ($input['per_unit_rate'] ?? 0);
        $fixed     = (float) ($input['fixed_charge'] ?? 0);
        $meterRent = (float) ($input['meter_rent'] ?? 0);
        $previous  = (float) ($input['previous_outstanding'] ?? 0);
        $paid      = (float) ($input['paid_amount'] ?? 0);

        $energyCharge = $this->energyCharge($type, $units, $rate);
        $lateFee = $this->lateFee($type, $previous);

        $total = round($energyCharge + $fixed + $meterRent + $previous + $lateFee, 2);
        $due = round($total - $paid, 2);

        return [
            'energy_charge' => $energyCharge,
            'late_fee'      => $lateFee,
            'total_amount'  => $total,
            'due_amount'    => $due,
            'status'        => $this->status($total, $paid),
        ];
    }

    /**
     * Energy charge: the minimum acts as a floor. Beyond the slab the metered
     * amount is (units - SLAB_UNITS) * rate; whichever is larger wins.
     */
    public function energyCharge(?string $type, float $units, float $rate): float
    {
        $minimum = $this->minimumCharge($type);
        $metered = max(0.0, $units - self::SLAB_UNITS) * $rate;

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
