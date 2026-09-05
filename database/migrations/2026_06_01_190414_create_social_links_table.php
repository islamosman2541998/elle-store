<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();

            $table->string('platform'); 
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();

            $table->string('url')->nullable();
            $table->string('icon')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(true);

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['platform', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};