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
        'changed_by',
    ];

    protected $casts = [
        'old_rate' => 'decimal:2',
        'new_rate' => 'decimal:2',
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
