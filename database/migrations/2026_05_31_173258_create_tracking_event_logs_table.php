<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_event_logs', function (Blueprint $table) {
            $table->id();

            $table->string('event_name');
            $table->string('event_id')->nullable();

            $table->string('source')->default('browser');
            $table->string('platform')->nullable();

            $table->string('status')->default('pending');

            $table->json('payload')->nullable();
            $table->json('response')->nullable();

            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['event_name', 'status']);
            $table->index(['platform', 'status']);
            $table->index(['event_id']);
            $table->index(['order_id']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_event_logs');
    }
};