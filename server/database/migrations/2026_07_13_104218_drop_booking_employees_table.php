<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaced by booking_items.employee_id - assignment is per service
     * line, not per whole booking. Keeping both would mean two sources
     * of truth for "who's on this booking."
     */
    public function up(): void
    {
        Schema::dropIfExists('booking_employees');
    }

    public function down(): void
    {
        Schema::create('booking_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['booking_id', 'employee_id']);
        });
    }
};
