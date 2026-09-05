<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('shipping_enabled')->default(true)->after('payment_proof_required');
            $table->boolean('global_free_shipping_enabled')->default(false)->after('shipping_enabled');
            $table->decimal('global_free_shipping_minimum', 10, 2)->nullable()->after('global_free_shipping_enabled');

            $table->integer('default_preparation_days')->default(1)->after('global_free_shipping_minimum');
            $table->integer('default_delivery_days')->default(3)->after('default_preparation_days');

            $table->boolean('show_tracking_to_customer')->default(true)->after('default_delivery_days');

            $table->text('shipping_policy_ar')->nullable()->after('show_tracking_to_customer');
            $table->text('shipping_policy_en')->nullable()->after('shipping_policy_ar');

            $table->text('shipping_notes_ar')->nullable()->after('shipping_policy_en');
            $table->text('shipping_notes_en')->nullable()->after('shipping_notes_ar');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_enabled',
                'global_free_shipping_enabled',
                'global_free_shipping_minimum',
                'default_preparation_days',
                'default_delivery_days',
                'show_tracking_to_customer',
                'shipping_policy_ar',
                'shipping_policy_en',
                'shipping_notes_ar',
                'shipping_notes_en',
            ]);
        });
    }
};