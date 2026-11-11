<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Values for a variation type, e.g. Size -> "Small", "Medium", "Large".
        Schema::create('variation_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['variation_type_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_options');
    }
};
