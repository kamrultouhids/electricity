<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\MeterReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opening balances for customers brought over from the old system.
 *
 * A migrated customer arrives with two things the new system has no other way
 * to learn: where their meter already stood, and what they still owe. Both are
 * written as ordinary rows — a meter reading and a bill — flagged as opening,
 * so every downstream calculation keeps working untouched:
 *
 *  - MeterReadingController::carriedPreviousReading() finds the meter anchor,
 *    so the first real bill charges the month's units, not the meter's lifetime.
 *  - BillGenerator::buildData() finds the carried due, so it rolls forward like
 *    any other unpaid month.
 *  - PaymentController::settleEarlierBills() finds a bill to collect against,
 *    oldest first, so the old debt is paid off before the new months.
 *
 * The opening bill is a ledger entry, not a billed month. It never passes
 * through BillCalculator: that would floor its energy charge at the connection
 * type's minimum (300 / 350) and invent a charge nobody ever incurred.
 */
class CustomerOpeningBalance
{
    /**
     * Write the opening reading and opening bill for a customer.
     *
     * Idempotent — a customer that already has an opening bill is left alone,
     * so re-running an import or a backfill can't double their debt.
     *
     * @return Bill|null The opening bill, or null when nothing was declared.
     */
    public function materialize(Customer $customer, ?int $userId = null): ?Bill
    {
        if (! $customer->hasOpeningBalance()) {
            return null;
        }

        return DB::transaction(function () use ($customer, $userId) {
            if ($existing = $this->openingBill($customer)) {
                return $existing;
            }

            $asOf = Carbon::parse($customer->opening_as_of)->toDateString();
            $reading = (float) ($customer->opening_reading ?? 0);
            $due = round((float) ($customer->opening_due ?? 0), 2);

            $this->writeReading($customer, $reading, $asOf, $userId);

            return $this->writeBill($customer, $due, $asOf, $userId);
        });
    }

    /**
     * Re-declare a customer's opening balance, replacing the derived rows.
     *
     * Only safe while nothing has been billed or collected on top of it —
     * see blockedReason(). Callers must check first.
     */
    public function adjust(Customer $customer, ?int $userId = null): ?Bill
    {
        return DB::transaction(function () use ($customer, $userId) {
            $this->clear($customer);

            return $this->materialize($customer->refresh(), $userId);
        });
    }

    /**
     * Remove a customer's derived opening rows, leaving the declared figures.
     */
    public function clear(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            Bill::query()
                ->where('customer_id', $customer->id)
                ->where('is_opening', true)
                ->forceDelete();

            MeterReading::query()
                ->where('customer_id', $customer->id)
                ->where('source', MeterReading::SOURCE_OPENING)
                ->forceDelete();
        });
    }

    /**
     * Why this customer's opening balance can no longer be changed, or null
     * when it still can.
     *
     * Mirrors the discipline in Bill::revisionBlockedReason(): once a later
     * bill has frozen the opening figure into its carried balance and printed
     * history, or once money has been taken against it, the ledger is history
     * and a correction has to be a new entry rather than a rewrite.
     */
    public function blockedReason(Customer $customer): ?string
    {
        $opening = $this->openingBill($customer);

        if ($opening && ! $opening->isUntouchedByPayment()) {
            return 'A payment has already been collected against this opening balance.';
        }

        // The checks below apply whether or not an opening balance was declared:
        // anchoring a meter or injecting a debt underneath a customer who has
        // already been billed from zero would break the chain just as badly as
        // rewriting a declared one.
        if ($customer->payments()->exists()) {
            return 'This customer has already made a payment.';
        }

        if ($this->hasRealBill($customer)) {
            return 'This customer has already been billed.';
        }

        if ($this->hasRealReading($customer)) {
            return 'A meter reading has already been taken for this customer.';
        }

        return null;
    }

    public function canAdjust(Customer $customer): bool
    {
        return $this->blockedReason($customer) === null;
    }

    /**
     * Anchor the meter. Consumption is zero by construction: previous equals
     * current, so the chain starts clean and flagDiscrepancies() sees no break.
     * Completed, so bill generation never picks it up as pending work.
     */
    protected function writeReading(Customer $customer, float $reading, string $asOf, ?int $userId): MeterReading
    {
        return MeterReading::create([
            'customer_id'      => $customer->id,
            'previous_reading' => $reading,
            'current_reading'  => $reading,
            'consumed_units'   => 0,
            'reading_date'     => $asOf,
            'status'           => MeterReading::STATUS_COMPLETED,
            'source'           => MeterReading::SOURCE_OPENING,
            'created_by'       => $userId,
            'updated_by'       => $userId,
        ]);
    }

    /**
     * Open the ledger with the carried debt. Every charge is a literal zero —
     * nothing here is computed, because there is nothing to compute: no units
     * were consumed under this system and no tariff applied to them.
     */
    protected function writeBill(Customer $customer, float $due, string $asOf, ?int $userId): Bill
    {
        return Bill::create([
            'customer_id'             => $customer->id,
            'meter_reading_id'        => null,
            // Stamped one month back from the anchor date, the same way a real
            // reading bills for the month before it: an anchor as of 31 Aug
            // opens July, leaving August free for the first real bill.
            'billing_month'           => Carbon::parse($asOf)->startOfMonth()->toDateString(),
            'units'                   => 0,
            'per_unit_rate'           => 0,
            'energy_charge'           => 0,
            'line_charge'             => 0,
            'service_charge'          => 0,
            'demand_charge'           => 0,
            'electricity_duty_rate'   => 0,
            'electricity_duty'        => 0,
            'fixed_charge'            => 0,
            'meter_rent'              => 0,
            'previous_outstanding'    => $due,
            'late_fee'                => 0,
            'total_amount'            => $due,
            'paid_amount'             => 0,
            'discount'                => 0,
            'due_amount'              => $due,
            'previous_bills_snapshot' => [],
            'status'                  => $due > 0 ? Bill::STATUS_UNPAID : Bill::STATUS_PAID,
            'is_opening'              => true,
            'created_by'              => $userId,
            'updated_by'              => $userId,
        ]);
    }

    protected function openingBill(Customer $customer): ?Bill
    {
        return Bill::query()
            ->where('customer_id', $customer->id)
            ->where('is_opening', true)
            ->first();
    }

    /**
     * Whether a real bill exists — anything that isn't the opening entry.
     */
    protected function hasRealBill(Customer $customer): bool
    {
        return Bill::query()
            ->where('customer_id', $customer->id)
            ->where('is_opening', false)
            ->exists();
    }

    /**
     * Whether a reading has been taken that isn't the opening anchor.
     */
    protected function hasRealReading(Customer $customer): bool
    {
        return MeterReading::query()
            ->where('customer_id', $customer->id)
            ->where('source', '!=', MeterReading::SOURCE_OPENING)
            ->exists();
    }
}
