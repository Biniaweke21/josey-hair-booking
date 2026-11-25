<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:send-reminders')->everyFifteenMinutes();
Schedule::command('bookings:auto-progress')->everyMinute();

// The free host has no separate queue worker, so drain the queue (booking
// emails) from the scheduler once a minute instead.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
