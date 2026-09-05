<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_cities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->constrained('shipping_zones')
                ->nullOnDelete();

            $table->string('name_ar');
            $table->string('name_en');

            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('free_shipping_min_order', 10, 2)->nullable();

            $table->integer('estimated_delivery_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['shipping_zone_id', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_cities');
    }
};