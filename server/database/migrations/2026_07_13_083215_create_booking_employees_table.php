<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replaces bookings.employee_id: a booking can have more than one
        // staff member assigned (e.g. two stylists on one large job).
        Schema::create('booking_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['booking_id', 'employee_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('booking_employees');
    }
};
