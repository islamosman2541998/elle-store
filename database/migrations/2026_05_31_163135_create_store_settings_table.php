<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            $table->string('store_name_ar')->nullable();
            $table->string('store_name_en')->nullable();

            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();

            $table->text('address_ar')->nullable();
            $table->text('address_en')->nullable();

            $table->string('currency_code')->default('EGP');
            $table->string('currency_symbol')->default('EGP');

            $table->string('default_locale')->default('ar');
            $table->boolean('is_store_active')->default(true);

            $table->text('maintenance_message_ar')->nullable();
            $table->text('maintenance_message_en')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};