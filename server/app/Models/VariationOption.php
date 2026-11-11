<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariationOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'variation_type_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    /**
     * Per-service price/duration adjustments for this option value.
     */
    public function serviceAdjustments(): HasMany
    {
        return $this->hasMany(ServiceVariationOption::class);
    }
}
