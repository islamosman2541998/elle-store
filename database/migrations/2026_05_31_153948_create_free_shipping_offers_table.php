<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_shipping_offers', function (Blueprint $table) {
            $table->id();

            $table->string('name_ar');
            $table->string('name_en');

            $table->decimal('minimum_order_amount', 10, 2)->nullable();

            $table->foreignId('shipping_city_id')
                ->nullable()
                ->constrained('shipping_cities')
                ->nullOnDelete();

            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->constrained('shipping_zones')
                ->nullOnDelete();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at']);
            $table->index(['shipping_city_id', 'is_active']);
            $table->index(['shipping_zone_id', 'is_active']);
            $table->index(['minimum_order_amount', 'is_active']);
            $table->index(['priority', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_shipping_offers');
    }
};