<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('invoices_enabled')->default(true)->after('shipping_notes_en');

            $table->string('invoice_prefix')->default('INV')->after('invoices_enabled');
            $table->string('invoice_number_format')->default('{prefix}-{year}{month}{day}-{id}')->after('invoice_prefix');

            $table->string('invoice_seller_name_ar')->nullable()->after('invoice_number_format');
            $table->string('invoice_seller_name_en')->nullable()->after('invoice_seller_name_ar');

            $table->string('tax_number')->nullable()->after('invoice_seller_name_en');
            $table->string('commercial_registration_number')->nullable()->after('tax_number');

            $table->text('invoice_address_ar')->nullable()->after('commercial_registration_number');
            $table->text('invoice_address_en')->nullable()->after('invoice_address_ar');

            $table->text('invoice_notes_ar')->nullable()->after('invoice_address_en');
            $table->text('invoice_notes_en')->nullable()->after('invoice_notes_ar');

            $table->text('invoice_terms_ar')->nullable()->after('invoice_notes_en');
            $table->text('invoice_terms_en')->nullable()->after('invoice_terms_ar');

            $table->boolean('show_logo_on_invoice')->default(true)->after('invoice_terms_en');
            $table->boolean('show_tax_on_invoice')->default(true)->after('show_logo_on_invoice');
            $table->boolean('show_payment_status_on_invoice')->default(true)->after('show_tax_on_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'invoices_enabled',
                'invoice_prefix',
                'invoice_number_format',
                'invoice_seller_name_ar',
                'invoice_seller_name_en',
                'tax_number',
                'commercial_registration_number',
                'invoice_address_ar',
                'invoice_address_en',
                'invoice_notes_ar',
                'invoice_notes_en',
                'invoice_terms_ar',
                'invoice_terms_en',
                'show_logo_on_invoice',
                'show_tax_on_invoice',
                'show_payment_status_on_invoice',
            ]);
        });
    }
};