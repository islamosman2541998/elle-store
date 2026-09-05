<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('original_unit_price', 10, 2)->nullable()->after('unit_price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_unit_price');
            $table->string('discount_source')->nullable()->after('discount_amount');

            $table->foreignId('flash_sale_item_id')
                ->nullable()
                ->after('discount_source')
                ->constrained('flash_sale_items')
                ->nullOnDelete();

            $table->timestamp('flash_sale_counted_at')->nullable()->after('flash_sale_item_id');

            $table->index(['flash_sale_item_id', 'flash_sale_counted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['flash_sale_item_id']);
            $table->dropIndex(['flash_sale_item_id', 'flash_sale_counted_at']);

            $table->dropColumn([
                'original_unit_price',
                'discount_amount',
                'discount_source',
                'flash_sale_item_id',
                'flash_sale_counted_at',
            ]);
        });
    }
};