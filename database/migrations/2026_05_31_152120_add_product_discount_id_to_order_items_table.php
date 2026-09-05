<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_discount_id')
                ->nullable()
                ->after('flash_sale_item_id')
                ->constrained('product_discounts')
                ->nullOnDelete();

            $table->index('product_discount_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_discount_id']);
            $table->dropIndex(['product_discount_id']);
            $table->dropColumn('product_discount_id');
        });
    }
};