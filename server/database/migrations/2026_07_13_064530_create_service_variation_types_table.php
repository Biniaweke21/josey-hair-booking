<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Declares which variation types are selectable on a given service
        // (e.g. Knotless Braids uses Size + Length + Color).
        Schema::create('service_variation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'variation_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_variation_types');
    }
};
