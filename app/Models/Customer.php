<?php

namespace App\Models;

use App\Models\Concerns\HasSource;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasSource, SoftDeletes;

    protected $fillable = [
        'sheet_id',
        'serial_no',
        'photo',
        'name',
        'father_or_husband_name',
        'mother_name',
        'mobile_number',
        'address',
        'educational_qualification',
        'age',
        'occupation',
        'religion',
        'national_id',
        'guardian_name',
        'guardian_relationship',
        'guardian_address',
        'meter_number',
        'connection_type',
        'connection_date',
        'opening_reading',
        'opening_due',
        'opening_as_of',
        'status',
        'source',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sheet_id' => 'integer',
        'age' => 'integer',
        'status' => 'integer',
        'connection_date' => 'date',
        'opening_reading' => 'decimal:2',
        'opening_due' => 'decimal:2',
        'opening_as_of' => 'date',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class)->latest('reading_date')->latest('id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date')->latest('id');
    }

    /**
     * The customer's most recent meter reading.
     */
    public function latestReading(): ?MeterReading
    {
        return $this->readings()->first();
    }

    /**
     * Eager-loadable "latest reading" relation (for search results, etc.).
     */
    public function latestMeterReading(): HasOne
    {
        return $this->hasOne(MeterReading::class)->latestOfMany('reading_date');
    }

    /**
     * Connection type options.
     */
    public const CONNECTION_TYPES = ['residential', 'commercial', 'religious', 'others'];

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether an opening balance was declared for this customer — i.e. they
     * came from the old system with a meter history, a debt, or both.
     * A zero due with a reading still counts: the meter has to start somewhere.
     */
    public function hasOpeningBalance(): bool
    {
        return $this->opening_as_of !== null;
    }

    /**
     * The carry-forward bill that opens a migrated customer's ledger, if any.
     */
    public function openingBill(): HasOne
    {
        return $this->hasOne(Bill::class)->where('is_opening', true);
    }

    /**
     * The reading the meter stood at when the customer was brought over.
     */
    public function openingReadingRow(): HasOne
    {
        return $this->hasOne(MeterReading::class)->where('source', MeterReading::SOURCE_OPENING);
    }
}
