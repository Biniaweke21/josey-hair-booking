<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_item_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name_snapshot');
            $table->decimal('price_snapshot', 10, 2);
            $table->unsignedInteger('duration_snapshot')->default(0);
            $table->timestamps();

            $table->index('booking_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_add_ons');
    }
};
