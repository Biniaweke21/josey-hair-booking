<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHourException extends Model
{
    protected $fillable = [
        'date',
        'open_time',
        'close_time',
        'is_closed',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'is_closed' => 'boolean',
    ];
}
