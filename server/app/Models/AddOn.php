<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddOn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_modifier',
        'duration_modifier_minutes',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'duration_modifier_minutes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_add_ons');
    }
}
