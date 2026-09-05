<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_city_id')
                ->nullable()
                ->after('customer_address_id')
                ->constrained('shipping_cities')
                ->nullOnDelete();

            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->after('shipping_city_id')
                ->constrained('shipping_zones')
                ->nullOnDelete();

            $table->string('shipping_discount_source')
                ->nullable()
                ->after('shipping_total');

            $table->foreignId('free_shipping_offer_id')
                ->nullable()
                ->after('shipping_discount_source')
                ->constrained('free_shipping_offers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_city_id']);
            $table->dropForeign(['shipping_zone_id']);
            $table->dropForeign(['free_shipping_offer_id']);

            $table->dropColumn([
                'shipping_city_id',
                'shipping_zone_id',
                'shipping_discount_source',
                'free_shipping_offer_id',
            ]);
        });
    }
};