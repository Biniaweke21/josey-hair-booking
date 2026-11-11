<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Breaks only had meaning against a per-employee shift timeline. This
     * salon reserves capacity by daily headcount, not shift times, so
     * within-shift breaks no longer affect anything.
     */
    public function up(): void
    {
        Schema::dropIfExists('employee_schedule_breaks');
    }

    public function down(): void
    {
        Schema::create('employee_schedule_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_weekly_schedule_id')->constrained()->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index('employee_weekly_schedule_id');
        });
    }
};
