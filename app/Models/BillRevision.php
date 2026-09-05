<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillRevision extends Model
{
    protected $fillable = [
        'bill_id',
        'meter_reading_id',
        'old_previous_reading',
        'new_previous_reading',
        'old_current_reading',
        'new_current_reading',
        'old_units',
        'new_units',
        'old_previous_outstanding',
        'new_previous_outstanding',
        'old_late_fee',
        'new_late_fee',
        'old_total_amount',
        'new_total_amount',
        'old_due_amount',
        'new_due_amount',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'old_previous_reading' => 'decimal:2',
        'new_previous_reading' => 'decimal:2',
        'old_current_reading' => 'decimal:2',
        'new_current_reading' => 'decimal:2',
        'old_units' => 'decimal:2',
        'new_units' => 'decimal:2',
        'old_previous_outstanding' => 'decimal:2',
        'new_previous_outstanding' => 'decimal:2',
        'old_late_fee' => 'decimal:2',
        'new_late_fee' => 'decimal:2',
        'old_total_amount' => 'decimal:2',
        'new_total_amount' => 'decimal:2',
        'old_due_amount' => 'decimal:2',
        'new_due_amount' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
