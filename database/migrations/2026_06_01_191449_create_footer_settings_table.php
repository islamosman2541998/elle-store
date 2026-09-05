<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_settings', function (Blueprint $table) {
            $table->id();

            $table->string('logo')->nullable();

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->string('copyright_ar')->nullable();
            $table->string('copyright_en')->nullable();

            $table->boolean('show_social_links')->default(true);
            $table->boolean('show_payment_methods')->default(true);
            $table->boolean('show_newsletter')->default(false);

            $table->string('newsletter_title_ar')->nullable();
            $table->string('newsletter_title_en')->nullable();
            $table->text('newsletter_description_ar')->nullable();
            $table->text('newsletter_description_en')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_settings');
    }
};