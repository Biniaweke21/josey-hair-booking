<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Message content lives in the database, not in code, so admins can
        // edit wording without a deploy. Body supports {{placeholder}} tokens.
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key'); // e.g. booking_confirmation, appointment_reminder, thank_you
            $table->enum('channel', ['email', 'sms']);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
