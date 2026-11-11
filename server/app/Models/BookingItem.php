<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_id',
        'service_id',
        'employee_id',
        'name_snapshot',
        'price_snapshot',
        'duration_snapshot',
        'sort_order',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
        'duration_snapshot' => 'integer',
        'sort_order' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function addOns(): HasMany
    {
        return $this->hasMany(BookingItemAddOn::class);
    }

    public function variationOptions(): HasMany
    {
        return $this->hasMany(BookingItemVariationOption::class);
    }
}
