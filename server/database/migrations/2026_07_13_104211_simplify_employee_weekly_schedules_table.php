<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This table becomes a pure daily roster ("is this employee working
     * Monday: yes/no") rather than a shift timeline - the booking engine
     * only ever needs a headcount per day, never a time-of-day window.
     */
    public function up(): void
    {
        // The employee_id foreign key always needs a supporting index, so
        // the new one has to exist before the old one can be dropped.
        Schema::table('employee_weekly_schedules', function (Blueprint $table) {
            $table->unique(['employee_id', 'day_of_week'], 'emp_weekly_schedule_unique');
        });

        Schema::table('employee_weekly_schedules', function (Blueprint $table) {
            $table->dropIndex('emp_weekly_schedule_lookup');
            $table->dropColumn(['start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_weekly_schedules', function (Blueprint $table) {
            $table->time('start_time')->default('09:00:00')->after('day_of_week');
            $table->time('end_time')->default('17:00:00')->after('start_time');
            $table->index(['employee_id', 'day_of_week', 'is_active'], 'emp_weekly_schedule_lookup');
        });

        Schema::table('employee_weekly_schedules', function (Blueprint $table) {
            $table->dropUnique('emp_weekly_schedule_unique');
        });
    }
};
