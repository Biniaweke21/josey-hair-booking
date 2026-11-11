<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    public function run(): void
    {
        // 0 = Sunday ... 6 = Saturday. Sensible defaults; fully editable by admin afterward.
        $defaults = [
            0 => ['is_closed' => true, 'open_time' => null, 'close_time' => null],
            1 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'],
            2 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'],
            3 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'],
            4 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'],
            5 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'],
            6 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
        ];

        foreach ($defaults as $day => $hours) {
            BusinessHour::firstOrCreate(['day_of_week' => $day], $hours);
        }
    }
}
