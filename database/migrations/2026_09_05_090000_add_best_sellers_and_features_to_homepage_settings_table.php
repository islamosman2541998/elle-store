<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            // "Best sellers" section, driven by real order history.
            $table->boolean('best_sellers_enabled')->default(true)->after('brands_limit');
            $table->string('best_sellers_title_ar')->nullable()->after('best_sellers_enabled');
            $table->string('best_sellers_title_en')->nullable()->after('best_sellers_title_ar');
            $table->integer('best_sellers_limit')->default(8)->after('best_sellers_title_en');

            // Static homepage bands: the trust/benefits strip and the
            // "follow us" strip.
            $table->boolean('features_strip_enabled')->default(true)->after('best_sellers_limit');
            $table->boolean('category_banner_enabled')->default(true)->after('features_strip_enabled');
            $table->boolean('social_strip_enabled')->default(true)->after('category_banner_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->dropColumn([
                'best_sellers_enabled',
                'best_sellers_title_ar',
                'best_sellers_title_en',
                'best_sellers_limit',
                'features_strip_enabled',
                'category_banner_enabled',
                'social_strip_enabled',
            ]);
        });
    }
};
