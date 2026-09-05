<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flash_sale_id')
                ->constrained('flash_sales')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->string('discount_type')->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);

            $table->integer('quantity_limit')->nullable();
            $table->integer('sold_count')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['flash_sale_id', 'product_id', 'product_variant_id'],
                'flash_sale_product_variant_unique'
            );

            $table->index(['flash_sale_id', 'is_active']);
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_items');
    }
};