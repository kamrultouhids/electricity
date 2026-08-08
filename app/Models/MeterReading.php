<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeterReading extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'previous_reading',
        'current_reading',
        'consumed_units',
        'reading_date',
        'photo',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
        'consumed_units' => 'decimal:2',
        'reading_date' => 'date',
        'status' => 'integer',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 1;
    public const STATUS_COMPLETED = 2;

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_COMPLETED => 'Completed',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    /**
     * A reading is inconsistent when the current reading is below the
     * previous one (a cumulative meter can't run backwards).
     */
    public function hasDiscrepancy(): bool
    {
        return (float) $this->current_reading < (float) $this->previous_reading;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
