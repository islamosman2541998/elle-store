<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('dashboard_primary_color')->nullable()->after('invoice_created_message_en');
            $table->string('dashboard_sidebar_color')->nullable()->after('dashboard_primary_color');
            $table->string('dashboard_sidebar_text_color')->nullable()->after('dashboard_sidebar_color');
            $table->string('dashboard_topbar_color')->nullable()->after('dashboard_sidebar_text_color');

            $table->integer('dashboard_button_radius')->default(8)->after('dashboard_topbar_color');
            $table->integer('dashboard_card_radius')->default(12)->after('dashboard_button_radius');

            $table->string('dashboard_logo')->nullable()->after('dashboard_card_radius');
            $table->string('dashboard_favicon')->nullable()->after('dashboard_logo');

            $table->boolean('dashboard_dark_mode_default')->default(false)->after('dashboard_favicon');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'dashboard_primary_color',
                'dashboard_sidebar_color',
                'dashboard_sidebar_text_color',
                'dashboard_topbar_color',
                'dashboard_button_radius',
                'dashboard_card_radius',
                'dashboard_logo',
                'dashboard_favicon',
                'dashboard_dark_mode_default',
            ]);
        });
    }
};