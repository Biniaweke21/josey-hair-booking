<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    /**
     * Statuses that still occupy salon capacity. Bookings are created
     * confirmed (no approval step), so this is exactly "in progress or
     * upcoming, not yet finished".
     */
    public const ACTIVE_STATUSES = ['confirmed', 'checked_in'];

    protected $fillable = [
        'customer_id',
        'status',
        'booking_date',
        'start_time',
        'end_time',
        'subtotal',
        'addon_total',
        'total_price',
        'total_duration_minutes',
        'notes',
        'source',
        'confirmed_at',
        'checked_in_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'subtotal' => 'decimal:2',
        'addon_total' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_duration_minutes' => 'integer',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Distinct employees assigned across this booking's service lines.
     * Derived from booking_items.employee_id - there's no longer a
     * separate whole-booking assignment table.
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'booking_items', 'booking_id', 'employee_id')->distinct();
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingEvent::class)->orderBy('created_at');
    }
}
