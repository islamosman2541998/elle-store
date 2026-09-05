<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('login_activity_logging_enabled')->default(true)->after('invoice_created_message_en');
            $table->integer('admin_session_timeout_minutes')->default(120)->after('login_activity_logging_enabled');

            $table->boolean('force_admin_password_change')->default(false)->after('admin_session_timeout_minutes');
            $table->integer('password_change_days')->nullable()->after('force_admin_password_change');

            $table->boolean('notify_admin_new_device_login')->default(false)->after('password_change_days');

            $table->boolean('customer_registration_enabled')->default(true)->after('notify_admin_new_device_login');
            $table->boolean('customer_login_enabled')->default(true)->after('customer_registration_enabled');

            $table->boolean('prevent_order_edit_after_delivery')->default(true)->after('customer_login_enabled');
            $table->boolean('prevent_order_edit_after_cancellation')->default(true)->after('prevent_order_edit_after_delivery');

            $table->integer('max_login_attempts')->default(5)->after('prevent_order_edit_after_cancellation');
            $table->integer('login_lockout_minutes')->default(15)->after('max_login_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'login_activity_logging_enabled',
                'admin_session_timeout_minutes',
                'force_admin_password_change',
                'password_change_days',
                'notify_admin_new_device_login',
                'customer_registration_enabled',
                'customer_login_enabled',
                'prevent_order_edit_after_delivery',
                'prevent_order_edit_after_cancellation',
                'max_login_attempts',
                'login_lockout_minutes',
            ]);
        });
    }
};