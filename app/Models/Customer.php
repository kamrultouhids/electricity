<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

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
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sheet_id' => 'integer',
        'age' => 'integer',
        'status' => 'integer',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(Sheet::class);
    }

    /**
     * Connection type options.
     */
    public const CONNECTION_TYPES = ['residential', 'commercial', 'irrigation', 'others'];

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
