<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'amount',
        'discount',
        'payment_date',
        'collector_id',
        'method',
        'note',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'payment_date' => 'date',
        'status' => 'integer',
    ];

    /**
     * Status constants.
     */
    public const STATUS_COMPLETED = 1;
    public const STATUS_CANCELLED = 2;

    /**
     * Payment method options.
     */
    public const METHODS = [
        'cash'   => 'Cash',
        'bkash'  => 'bKash',
        'nagad'  => 'Nagad',
        'rocket' => 'Rocket',
        'bank'   => 'Bank',
        'other'  => 'Other',
    ];

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }

    /**
     * Formatted receipt number: RCP-YYYYMMDD-NNNN, where NNNN is this
     * payment's sequence within its payment date.
     */
    public function receiptNo(): string
    {
        $date = $this->payment_date ?? $this->created_at;

        $sequence = static::query()
            ->whereDate('payment_date', $this->payment_date)
            ->where('id', '<=', $this->id)
            ->count();

        return 'RCP-'.$date->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
