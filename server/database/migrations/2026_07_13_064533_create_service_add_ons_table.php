<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which add-ons are offered for which services.
        Schema::create('service_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_id', 'add_on_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_add_ons');
    }
};
