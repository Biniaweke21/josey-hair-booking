<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            // Assigned later by admin - customers never choose an employee.
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('pending');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('addon_total', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->unsignedInteger('total_duration_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->enum('source', ['website', 'admin', 'phone', 'walk_in'])->default('website');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Core index for the capacity/overlap query - the most
            // performance-critical lookup in the whole schema.
            $table->index(['booking_date', 'start_time', 'end_time', 'status'], 'bookings_capacity_lookup');
            $table->index('customer_id');
            $table->index('employee_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
