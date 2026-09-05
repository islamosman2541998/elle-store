<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable message bodies for every notification the store sends.
 *
 * One row per (event, channel). A blank body falls back to the built-in
 * default, so an empty table still sends sensible messages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            $table->string('event', 60);
            $table->string('channel', 20);

            // Email only; WhatsApp has no subject line.
            $table->string('subject_ar')->nullable();
            $table->string('subject_en')->nullable();

            $table->text('body_ar')->nullable();
            $table->text('body_en')->nullable();

            // The call-to-action button in the email layout.
            $table->string('button_label_ar')->nullable();
            $table->string('button_label_en')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['event', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
