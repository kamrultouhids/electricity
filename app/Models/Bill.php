<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'meter_reading_id',
        'billing_month',
        'units',
        'per_unit_rate',
        'energy_charge',
        'line_charge',
        'service_charge',
        'demand_charge',
        'electricity_duty_rate',
        'electricity_duty',
        'fixed_charge',
        'meter_rent',
        'previous_outstanding',
        'late_fee',
        'total_amount',
        'paid_amount',
        'discount',
        'due_amount',
        'previous_bills_snapshot',
        'status',
        'is_opening',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'units' => 'decimal:2',
        'per_unit_rate' => 'decimal:2',
        'energy_charge' => 'decimal:2',
        'line_charge' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'demand_charge' => 'decimal:2',
        'electricity_duty_rate' => 'decimal:2',
        'electricity_duty' => 'decimal:2',
        'fixed_charge' => 'decimal:2',
        'meter_rent' => 'decimal:2',
        'previous_outstanding' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'previous_bills_snapshot' => 'array',
        'status' => 'integer',
        'is_opening' => 'boolean',
    ];

    /**
     * Status constants.
     */
    /** How many previous months the bill document shows. */
    public const HISTORY_MONTHS = 3;

    public const STATUS_UNPAID = 1;
    public const STATUS_PARTIAL = 2;
    public const STATUS_PAID = 3;

    public const STATUS_LABELS = [
        self::STATUS_UNPAID => 'Unpaid',
        self::STATUS_PARTIAL => 'Partial',
        self::STATUS_PAID => 'Paid',
    ];

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    /**
     * The previous-months rows for the bill document, taken from the snapshot
     * stored at generation time. Falls back to a live lookup for bills that
     * were generated before snapshots existed.
     */
    public function historyRows(): Collection
    {
        if (is_array($this->previous_bills_snapshot)) {
            return self::mapHistoryRows($this->previous_bills_snapshot);
        }

        return static::query()
            ->where('customer_id', $this->customer_id)
            ->whereDate('billing_month', '<', $this->billing_month)
            ->orderByDesc('billing_month')
            ->limit(self::HISTORY_MONTHS)
            ->get();
    }

    /**
     * Shape raw snapshot rows into objects the bill document can render.
     */
    public static function mapHistoryRows(?array $rows): Collection
    {
        return collect($rows ?? [])->map(fn (array $row) => (object) [
            'billing_month' => Carbon::parse($row['billing_month']),
            'units'         => (float) ($row['units'] ?? 0),
            'total_amount'  => (float) ($row['total_amount'] ?? 0),
            'paid_amount'   => (float) ($row['paid_amount'] ?? 0),
            'discount'      => (float) ($row['discount'] ?? 0),
            'due_amount'    => (float) ($row['due_amount'] ?? 0),
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BillRevision::class)->latest();
    }

    /**
     * Whether this is the customer's newest bill. Only that one may be revised:
     * a later bill would have frozen this bill's figures into its carried
     * balance and its printed history.
     */
    public function isLatestForCustomer(): bool
    {
        return ! static::query()
            ->where('customer_id', $this->customer_id)
            ->where(function ($q) {
                $q->whereDate('billing_month', '>', $this->billing_month)
                    ->orWhere(function ($q) {
                        $q->whereDate('billing_month', '=', $this->billing_month)
                            ->where('id', '>', $this->id);
                    });
            })
            ->exists();
    }

    /**
     * Nothing collected against this bill yet — no payment, no discount.
     */
    public function isUntouchedByPayment(): bool
    {
        return (float) $this->paid_amount <= 0
            && (float) $this->discount <= 0
            && ! $this->allocations()->exists();
    }

    /**
     * Why this bill cannot be revised, or null when it can be.
     */
    public function revisionBlockedReason(): ?string
    {
        if (! $this->meter_reading_id) {
            return 'This bill has no meter reading attached to revise.';
        }

        if (! $this->isLatestForCustomer()) {
            return 'Only the customer\'s latest bill can be revised.';
        }

        if (! $this->isUntouchedByPayment()) {
            return 'A payment has already been collected against this bill.';
        }

        return null;
    }

    public function isRevisable(): bool
    {
        return $this->revisionBlockedReason() === null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
