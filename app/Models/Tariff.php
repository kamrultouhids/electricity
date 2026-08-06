<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tariff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'connection_type',
        'per_unit_rate',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'per_unit_rate' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TariffLog::class)->latest();
    }

    /**
     * Resolve the active tariff for a connection type.
     */
    public static function resolveFor(string $connectionType): ?self
    {
        return static::query()
            ->where('connection_type', $connectionType)
            ->where('status', self::STATUS_ACTIVE)
            ->first();
    }
}
