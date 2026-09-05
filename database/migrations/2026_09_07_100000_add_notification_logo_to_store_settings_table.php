<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A logo just for the messages the store sends.
 *
 * The storefront logo is sized for a website header; the one that rides along
 * with an email or a WhatsApp message often wants to be different, so it gets
 * its own slot and falls back to the storefront logo when left empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('notification_logo')->nullable()->after('logo');

            // Sending an image costs a message credit and needs a publicly
            // reachable URL, so it is opt-in per channel.
            $table->boolean('whatsapp_send_logo')->default(false)->after('whatsapp_from_number');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['notification_logo', 'whatsapp_send_logo']);
        });
    }
};
