<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hot path: run on every single availability check and booking
        // attempt (AvailabilityService::capacityForDate/availableEmployeesFor).
        // The existing unique index is (employee_id, day_of_week) - useless
        // for a query that filters by day_of_week without employee_id.
        Schema::table('employee_weekly_schedules', function (Blueprint $table) {
            $table->index(['day_of_week', 'is_active']);
        });

        // Hot path: the admin bell/sidebar badge polls this exact
        // (type, read_at IS NULL) shape every 30s per open admin session.
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->index(['type', 'read_at']);
        });

        // Default admin customers list sorts by name with no filter applied.
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('employee_weekly_schedules', function (Blueprint $table) {
            $table->dropIndex(['day_of_week', 'is_active']);
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex(['type', 'read_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
