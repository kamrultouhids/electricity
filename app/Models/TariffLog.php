<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffLog extends Model
{
    protected $fillable = [
        'tariff_id',
        'connection_type',
        'old_rate',
        'new_rate',
        'old_line_charge',
        'new_line_charge',
        'old_service_charge',
        'new_service_charge',
        'old_demand_charge',
        'new_demand_charge',
        'changed_by',
    ];

    protected $casts = [
        'old_rate' => 'decimal:2',
        'new_rate' => 'decimal:2',
        'old_line_charge' => 'decimal:2',
        'new_line_charge' => 'decimal:2',
        'old_service_charge' => 'decimal:2',
        'new_service_charge' => 'decimal:2',
        'old_demand_charge' => 'decimal:2',
        'new_demand_charge' => 'decimal:2',
    ];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
