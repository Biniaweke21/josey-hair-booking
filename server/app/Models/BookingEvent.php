<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingEvent extends Model
{
    protected $fillable = [
        'booking_id',
        'type',
        'description',
        'meta',
        'caused_by_user_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function causedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caused_by_user_id');
    }
}
