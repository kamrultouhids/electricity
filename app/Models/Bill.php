<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'fixed_charge',
        'meter_rent',
        'previous_outstanding',
        'late_fee',
        'total_amount',
        'paid_amount',
        'discount',
        'due_amount',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'units' => 'decimal:2',
        'per_unit_rate' => 'decimal:2',
        'energy_charge' => 'decimal:2',
        'fixed_charge' => 'decimal:2',
        'meter_rent' => 'decimal:2',
        'previous_outstanding' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Status constants.
     */
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
