<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItemAddOn extends Model
{
    protected $fillable = [
        'booking_item_id',
        'add_on_id',
        'name_snapshot',
        'price_snapshot',
        'duration_snapshot',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'duration_snapshot' => 'integer',
    ];

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function addOn(): BelongsTo
    {
        return $this->belongsTo(AddOn::class);
    }
}
