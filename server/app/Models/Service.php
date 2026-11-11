<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'base_duration_minutes',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'base_duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variationTypes(): BelongsToMany
    {
        return $this->belongsToMany(VariationType::class, 'service_variation_types')
            ->withPivot('sort_order')
            ->orderBy('service_variation_types.sort_order');
    }

    public function variationOptions(): HasMany
    {
        return $this->hasMany(ServiceVariationOption::class);
    }

    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'service_add_ons');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
