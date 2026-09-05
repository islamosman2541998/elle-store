<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('email_notifications_enabled')->default(true)->after('show_payment_status_on_invoice');
            $table->string('admin_notification_email')->nullable()->after('email_notifications_enabled');

            $table->boolean('notify_admin_new_order')->default(true)->after('admin_notification_email');
            $table->boolean('notify_admin_new_payment')->default(true)->after('notify_admin_new_order');
            $table->boolean('notify_customer_order_status')->default(true)->after('notify_admin_new_payment');
            $table->boolean('notify_customer_invoice_created')->default(true)->after('notify_customer_order_status');

            $table->boolean('whatsapp_notifications_enabled')->default(false)->after('notify_customer_invoice_created');
            $table->string('whatsapp_api_provider')->nullable()->after('whatsapp_notifications_enabled');
            $table->text('whatsapp_api_token')->nullable()->after('whatsapp_api_provider');

            $table->text('new_order_email_subject_ar')->nullable()->after('whatsapp_api_token');
            $table->text('new_order_email_subject_en')->nullable()->after('new_order_email_subject_ar');

            $table->text('order_status_message_ar')->nullable()->after('new_order_email_subject_en');
            $table->text('order_status_message_en')->nullable()->after('order_status_message_ar');

            $table->text('invoice_created_message_ar')->nullable()->after('order_status_message_en');
            $table->text('invoice_created_message_en')->nullable()->after('invoice_created_message_ar');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications_enabled',
                'admin_notification_email',
                'notify_admin_new_order',
                'notify_admin_new_payment',
                'notify_customer_order_status',
                'notify_customer_invoice_created',
                'whatsapp_notifications_enabled',
                'whatsapp_api_provider',
                'whatsapp_api_token',
                'new_order_email_subject_ar',
                'new_order_email_subject_en',
                'order_status_message_ar',
                'order_status_message_en',
                'invoice_created_message_ar',
                'invoice_created_message_en',
            ]);
        });
    }
};