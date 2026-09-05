<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('slider_enabled')->default(true);

            $table->boolean('featured_categories_enabled')->default(true);
            $table->string('featured_categories_title_ar')->nullable();
            $table->string('featured_categories_title_en')->nullable();
            $table->integer('featured_categories_limit')->default(8);

            $table->boolean('featured_products_enabled')->default(true);
            $table->string('featured_products_title_ar')->nullable();
            $table->string('featured_products_title_en')->nullable();
            $table->integer('featured_products_limit')->default(8);

            $table->boolean('new_products_enabled')->default(true);
            $table->string('new_products_title_ar')->nullable();
            $table->string('new_products_title_en')->nullable();
            $table->integer('new_products_limit')->default(8);

            $table->boolean('flash_sales_enabled')->default(true);
            $table->string('flash_sales_title_ar')->nullable();
            $table->string('flash_sales_title_en')->nullable();
            $table->integer('flash_sales_limit')->default(8);

            $table->boolean('brands_enabled')->default(true);
            $table->string('brands_title_ar')->nullable();
            $table->string('brands_title_en')->nullable();
            $table->integer('brands_limit')->default(10);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_settings');
    }
};