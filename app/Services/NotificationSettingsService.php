<?php

namespace App\Services;

use App\Models\StoreSetting;

class NotificationSettingsService
{
    public function settings(): StoreSetting
    {
        return StoreSetting::current();
    }

    public function emailEnabled(): bool
    {
        return (bool) $this->settings()->email_notifications_enabled;
    }

    public function whatsappEnabled(): bool
    {
        return (bool) $this->settings()->whatsapp_notifications_enabled;
    }

    public function adminEmail(): ?string
    {
        return $this->settings()->admin_notification_email
            ?: $this->settings()->email;
    }

    public function shouldNotifyAdminNewOrder(): bool
    {
        return $this->emailEnabled()
            && (bool) $this->settings()->notify_admin_new_order
            && filled($this->adminEmail());
    }

    public function shouldNotifyAdminNewPayment(): bool
    {
        return $this->emailEnabled()
            && (bool) $this->settings()->notify_admin_new_payment
            && filled($this->adminEmail());
    }

    public function shouldNotifyCustomerOrderStatus(): bool
    {
        return $this->emailEnabled()
            && (bool) $this->settings()->notify_customer_order_status;
    }

    public function shouldNotifyCustomerInvoiceCreated(): bool
    {
        return $this->emailEnabled()
            && (bool) $this->settings()->notify_customer_invoice_created;
    }

    public function shouldNotifyCustomerNewOrder(): bool
    {
        return $this->emailEnabled()
            && (bool) $this->settings()->notify_customer_new_order;
    }

    public function shouldNotifyCustomerShipment(): bool
    {
        return $this->emailEnabled()
            && (bool) $this->settings()->notify_customer_shipment;
    }

    public function whatsappProvider(): ?string
    {
        return $this->settings()->whatsapp_api_provider;
    }

    public function whatsappToken(): ?string
    {
        return $this->settings()->whatsapp_api_token;
    }
}