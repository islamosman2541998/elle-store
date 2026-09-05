<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How strongly to veil the popup's background image.
 *
 * The image now sits behind the whole card, so the shop needs a way to keep
 * the wording readable over whatever picture it chooses.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('store_settings', 'coupon_popup_overlay')) {
            return;
        }

        Schema::table('store_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('coupon_popup_overlay')->default(80);
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('coupon_popup_overlay');
        });
    }
};
