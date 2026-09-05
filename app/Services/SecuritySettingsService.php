<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreSetting;

class SecuritySettingsService
{
    public function settings(): StoreSetting
    {
        return StoreSetting::current();
    }

    public function loginActivityLoggingEnabled(): bool
    {
        return (bool) $this->settings()->login_activity_logging_enabled;
    }

    public function adminSessionTimeoutMinutes(): int
    {
        return (int) ($this->settings()->admin_session_timeout_minutes ?: 120);
    }

    public function shouldForceAdminPasswordChange(): bool
    {
        return (bool) $this->settings()->force_admin_password_change;
    }

    public function passwordChangeDays(): ?int
    {
        return $this->settings()->password_change_days
            ? (int) $this->settings()->password_change_days
            : null;
    }

    public function shouldNotifyAdminNewDeviceLogin(): bool
    {
        return (bool) $this->settings()->notify_admin_new_device_login;
    }

    public function customerRegistrationEnabled(): bool
    {
        return (bool) $this->settings()->customer_registration_enabled;
    }

    public function customerLoginEnabled(): bool
    {
        return (bool) $this->settings()->customer_login_enabled;
    }

    public function maxLoginAttempts(): int
    {
        return (int) ($this->settings()->max_login_attempts ?: 5);
    }

    public function loginLockoutMinutes(): int
    {
        return (int) ($this->settings()->login_lockout_minutes ?: 15);
    }

    public function canEditOrder(Order $order): bool
    {
        if (
            $order->status === 'delivered'
            && $this->settings()->prevent_order_edit_after_delivery
        ) {
            return false;
        }

        if (
            $order->status === 'cancelled'
            && $this->settings()->prevent_order_edit_after_cancellation
        ) {
            return false;
        }

        return true;
    }
}