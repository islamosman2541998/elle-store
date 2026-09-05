<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_links', function (Blueprint $table) {
            $table->id();

            $table->string('location')->default('header'); // header, footer, mobile
            $table->string('link_type')->default('custom'); // custom, page, category, brand

            $table->foreignIdFor(Page::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Category::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Brand::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('title_ar');
            $table->string('title_en');

            $table->string('url')->nullable();

            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['location', 'is_active', 'sort_order']);
            $table->index('link_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_links');
    }
};