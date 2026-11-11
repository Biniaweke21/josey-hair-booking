<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recurring weekly working pattern per employee. Multiple rows per
        // day are allowed to support split shifts (e.g. morning + evening).
        Schema::create('employee_weekly_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday ... 6 = Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'day_of_week', 'is_active'], 'emp_weekly_schedule_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_weekly_schedules');
    }
};
