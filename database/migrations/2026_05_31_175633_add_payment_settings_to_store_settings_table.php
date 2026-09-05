<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('cash_on_delivery_enabled')->default(true)->after('cookie_consent_message_en');
            $table->boolean('bank_transfer_enabled')->default(false)->after('cash_on_delivery_enabled');
            $table->boolean('wallet_transfer_enabled')->default(false)->after('bank_transfer_enabled');

            $table->decimal('cash_on_delivery_fee', 10, 2)->default(0)->after('wallet_transfer_enabled');

            $table->text('bank_account_details_ar')->nullable()->after('cash_on_delivery_fee');
            $table->text('bank_account_details_en')->nullable()->after('bank_account_details_ar');

            $table->text('wallet_details_ar')->nullable()->after('bank_account_details_en');
            $table->text('wallet_details_en')->nullable()->after('wallet_details_ar');

            $table->text('payment_instructions_ar')->nullable()->after('wallet_details_en');
            $table->text('payment_instructions_en')->nullable()->after('payment_instructions_ar');

            $table->boolean('payment_proof_required')->default(false)->after('payment_instructions_en');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cash_on_delivery_enabled',
                'bank_transfer_enabled',
                'wallet_transfer_enabled',
                'cash_on_delivery_fee',
                'bank_account_details_ar',
                'bank_account_details_en',
                'wallet_details_ar',
                'wallet_details_en',
                'payment_instructions_ar',
                'payment_instructions_en',
                'payment_proof_required',
            ]);
        });
    }
};