<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->boolean('show_hero_slider')->default(true)->after('is_active');
            $table->boolean('show_featured_categories')->default(true)->after('show_hero_slider');
            $table->boolean('show_featured_products')->default(true)->after('show_featured_categories');
            $table->boolean('show_new_arrivals')->default(true)->after('show_featured_products');
            $table->boolean('show_flash_sales')->default(true)->after('show_new_arrivals');
            $table->boolean('show_brands')->default(true)->after('show_flash_sales');
            $table->boolean('show_blogs')->default(true)->after('show_brands');
            $table->boolean('show_newsletter')->default(true)->after('show_blogs');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_hero_slider',
                'show_featured_categories',
                'show_featured_products',
                'show_new_arrivals',
                'show_flash_sales',
                'show_brands',
                'show_blogs',
                'show_newsletter',
            ]);
        });
    }
};