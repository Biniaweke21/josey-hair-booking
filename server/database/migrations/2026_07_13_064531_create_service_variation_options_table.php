<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How much a specific variation value (e.g. Size:Large) adjusts price
        // and duration for one specific service. The same global option can
        // carry a different adjustment on every service that uses it.
        Schema::create('service_variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_option_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_adjustment', 10, 2)->default(0);
            $table->integer('duration_adjustment_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['service_id', 'variation_option_id'], 'sv_option_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_variation_options');
    }
};
