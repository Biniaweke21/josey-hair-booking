<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceVariationOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'variation_option_id',
        'price_adjustment',
        'duration_adjustment_minutes',
        'is_active',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'duration_adjustment_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function variationOption(): BelongsTo
    {
        return $this->belongsTo(VariationOption::class);
    }
}
