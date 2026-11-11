<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A break window (e.g. lunch) inside one weekly shift. Must fall
        // within the parent schedule's start/end time.
        Schema::create('employee_schedule_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_weekly_schedule_id')->constrained()->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index('employee_weekly_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_breaks');
    }
};
