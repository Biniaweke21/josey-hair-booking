<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which variation values were selected on a booking line item.
        // Snapshotted like everything else in a booking so history stays
        // accurate even if the catalog changes later.
        Schema::create('booking_item_variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variation_type_name_snapshot');
            $table->string('name_snapshot');
            $table->decimal('price_adjustment_snapshot', 10, 2)->default(0);
            $table->integer('duration_adjustment_snapshot')->default(0);
            $table->timestamps();

            $table->index('booking_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_variation_options');
    }
};
