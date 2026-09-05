<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('login_background_image')->nullable()->after('dashboard_favicon');
            $table->decimal('login_background_opacity', 3, 2)->default(0.35)->after('login_background_image');

            $table->string('login_card_background_color')->nullable()->after('login_background_opacity');
            $table->decimal('login_card_opacity', 3, 2)->default(0.92)->after('login_card_background_color');

            $table->boolean('login_card_blur')->default(true)->after('login_card_opacity');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'login_background_image',
                'login_background_opacity',
                'login_card_background_color',
                'login_card_opacity',
                'login_card_blur',
            ]);
        });
    }
};