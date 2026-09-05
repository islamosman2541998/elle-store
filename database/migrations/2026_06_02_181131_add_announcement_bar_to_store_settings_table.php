<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('announcement_bar_enabled')->default(false)->after('is_store_active');

            $table->string('announcement_bar_text_ar')->nullable()->after('announcement_bar_enabled');
            $table->string('announcement_bar_text_en')->nullable()->after('announcement_bar_text_ar');

            $table->string('announcement_bar_url')->nullable()->after('announcement_bar_text_en');
            $table->boolean('announcement_bar_open_in_new_tab')->default(false)->after('announcement_bar_url');

            $table->string('announcement_bar_bg_color')->nullable()->after('announcement_bar_open_in_new_tab');
            $table->string('announcement_bar_text_color')->nullable()->after('announcement_bar_bg_color');

            $table->integer('announcement_bar_speed')->default(25)->after('announcement_bar_text_color');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'announcement_bar_enabled',
                'announcement_bar_text_ar',
                'announcement_bar_text_en',
                'announcement_bar_url',
                'announcement_bar_open_in_new_tab',
                'announcement_bar_bg_color',
                'announcement_bar_text_color',
                'announcement_bar_speed',
            ]);
        });
    }
};