<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItemVariationOption extends Model
{
    protected $fillable = [
        'booking_item_id',
        'variation_option_id',
        'variation_type_name_snapshot',
        'name_snapshot',
        'price_adjustment_snapshot',
        'duration_adjustment_snapshot',
    ];

    protected $casts = [
        'price_adjustment_snapshot' => 'decimal:2',
        'duration_adjustment_snapshot' => 'integer',
    ];

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function variationOption(): BelongsTo
    {
        return $this->belongsTo(VariationOption::class);
    }
}
