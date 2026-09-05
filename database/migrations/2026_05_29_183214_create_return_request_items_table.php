<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('return_request_id')
                ->constrained('return_requests')
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku')->nullable();

            $table->integer('quantity')->default(1);

            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('refund_subtotal', 10, 2)->default(0);

            $table->text('reason')->nullable();

            $table->json('snapshot')->nullable();

            $table->timestamps();

            $table->index(['return_request_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_request_items');
    }
};