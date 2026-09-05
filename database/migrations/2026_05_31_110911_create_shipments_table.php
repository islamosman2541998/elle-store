<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->string('shipment_number')->unique();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('shipping_company_id')
                ->nullable()
                ->constrained('shipping_companies')
                ->nullOnDelete();

            $table->foreignId('shipping_city_id')
                ->nullable()
                ->constrained('shipping_cities')
                ->nullOnDelete();

            $table->string('status')->default('pending');

            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();

            $table->decimal('shipping_fee', 10, 2)->default(0);

            $table->json('shipping_address_snapshot')->nullable();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'status']);
            $table->index(['shipping_company_id', 'status']);
            $table->index(['shipping_city_id', 'status']);
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};