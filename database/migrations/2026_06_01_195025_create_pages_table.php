<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            $table->string('title_ar');
            $table->string('title_en');

            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();

            $table->text('short_description_ar')->nullable();
            $table->text('short_description_en')->nullable();

            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();

            $table->string('main_image')->nullable();

            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('slug_ar');
            $table->index('slug_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};