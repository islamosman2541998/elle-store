<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            // ---- Outgoing mail, so the shop is not editing .env -----------
            $table->string('mail_mailer')->nullable()->after('email_notifications_enabled');
            $table->string('mail_host')->nullable()->after('mail_mailer');
            $table->unsignedInteger('mail_port')->nullable()->after('mail_host');
            $table->string('mail_username')->nullable()->after('mail_port');
            $table->text('mail_password')->nullable()->after('mail_username');
            $table->string('mail_encryption')->nullable()->after('mail_password');
            $table->string('mail_from_address')->nullable()->after('mail_encryption');
            $table->string('mail_from_name')->nullable()->after('mail_from_address');

            // ---- WhatsApp provider credentials ----------------------------
            $table->string('whatsapp_api_url')->nullable()->after('whatsapp_api_token');
            // Cloud API phone number id / UltraMsg instance id / Twilio SID.
            $table->string('whatsapp_sender_id')->nullable()->after('whatsapp_api_url');
            // Twilio needs a second secret alongside the SID.
            $table->text('whatsapp_api_secret')->nullable()->after('whatsapp_sender_id');
            // The WhatsApp number messages are sent from (Twilio "from").
            $table->string('whatsapp_from_number')->nullable()->after('whatsapp_api_secret');

            // ---- Per-event WhatsApp switches, mirroring the email ones ----
            $table->boolean('whatsapp_notify_admin_new_order')->default(true)->after('whatsapp_from_number');
            $table->boolean('whatsapp_notify_admin_new_payment')->default(true)->after('whatsapp_notify_admin_new_order');
            $table->boolean('whatsapp_notify_customer_new_order')->default(true)->after('whatsapp_notify_admin_new_payment');
            $table->boolean('whatsapp_notify_customer_order_status')->default(true)->after('whatsapp_notify_customer_new_order');
            $table->boolean('whatsapp_notify_customer_invoice')->default(false)->after('whatsapp_notify_customer_order_status');
            $table->boolean('whatsapp_notify_customer_shipment')->default(true)->after('whatsapp_notify_customer_invoice');

            // ---- Email switches that had no column yet --------------------
            $table->boolean('notify_customer_new_order')->default(true)->after('notify_admin_new_payment');
            $table->boolean('notify_customer_shipment')->default(true)->after('notify_customer_invoice_created');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
                'whatsapp_api_url',
                'whatsapp_sender_id',
                'whatsapp_api_secret',
                'whatsapp_from_number',
                'whatsapp_notify_admin_new_order',
                'whatsapp_notify_admin_new_payment',
                'whatsapp_notify_customer_new_order',
                'whatsapp_notify_customer_order_status',
                'whatsapp_notify_customer_invoice',
                'whatsapp_notify_customer_shipment',
                'notify_customer_new_order',
                'notify_customer_shipment',
            ]);
        });
    }
};
