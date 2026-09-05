<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            // 'manual' keeps the existing behaviour (type the AWB by hand).
            $table->string('integration_driver')->default('manual')->after('code');

            $table->string('api_base_url')->nullable()->after('integration_driver');
            // Encrypted at rest via the model cast.
            $table->text('api_key')->nullable()->after('api_base_url');
            $table->text('webhook_secret')->nullable()->after('api_key');

            // Optional carrier-side identifiers (Bosta pickup location etc.).
            $table->string('pickup_location_id')->nullable()->after('webhook_secret');

            $table->boolean('auto_create_shipment')->default(false)->after('pickup_location_id');
            $table->boolean('sandbox_mode')->default(true)->after('auto_create_shipment');
        });

        Schema::table('shipments', function (Blueprint $table) {
            // The carrier's own id for this delivery, separate from the AWB.
            $table->string('carrier_shipment_id')->nullable()->after('tracking_url');
            // Raw state string as the carrier reports it, kept for debugging.
            $table->string('carrier_status')->nullable()->after('carrier_shipment_id');
            $table->timestamp('carrier_synced_at')->nullable()->after('carrier_status');
            $table->json('carrier_payload')->nullable()->after('carrier_synced_at');
            $table->text('carrier_error')->nullable()->after('carrier_payload');

            $table->index('carrier_shipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            $table->dropColumn([
                'integration_driver',
                'api_base_url',
                'api_key',
                'webhook_secret',
                'pickup_location_id',
                'auto_create_shipment',
                'sandbox_mode',
            ]);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex(['carrier_shipment_id']);
            $table->dropColumn([
                'carrier_shipment_id',
                'carrier_status',
                'carrier_synced_at',
                'carrier_payload',
                'carrier_error',
            ]);
        });
    }
};
