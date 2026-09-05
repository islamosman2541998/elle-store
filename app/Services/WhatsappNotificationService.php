<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\StoreSetting;
use App\Services\Notifications\MessageBuilder;
use App\Services\Notifications\WhatsappGateway;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp messages for order events.
 *
 * Each event has its own switch in the dashboard, so the shop can send order
 * confirmations over WhatsApp without also sending, say, invoice messages.
 * The wording comes from the notification templates, edited from the dashboard.
 *
 * Nothing here throws: if WhatsApp is off or unconfigured the call is a no-op.
 */
class WhatsappNotificationService
{
    public function __construct(
        private readonly WhatsappGateway $gateway,
        private readonly MessageBuilder $builder,
    ) {
    }

    public function notifyAdminNewOrder(Order $order): void
    {
        $settings = StoreSetting::current();

        if (! $this->canSend($settings, 'whatsapp_notify_admin_new_order') || blank($settings->whatsapp)) {
            return;
        }

        $this->send($settings->whatsapp, 'admin_new_order', $this->builder->orderTokens($order));
    }

    public function notifyCustomerNewOrder(Order $order): void
    {
        $settings = StoreSetting::current();

        if (! $this->canSend($settings, 'whatsapp_notify_customer_new_order') || blank($order->customer_phone)) {
            return;
        }

        $this->send($order->customer_phone, 'customer_new_order', $this->builder->orderTokens($order));
    }

    public function notifyCustomerOrderStatus(Order $order): void
    {
        $settings = StoreSetting::current();

        if (! $this->canSend($settings, 'whatsapp_notify_customer_order_status') || blank($order->customer_phone)) {
            return;
        }

        $this->send($order->customer_phone, 'customer_order_status', $this->builder->orderTokens($order));
    }

    public function notifyAdminNewPayment(Order $order): void
    {
        $settings = StoreSetting::current();

        if (! $this->canSend($settings, 'whatsapp_notify_admin_new_payment') || blank($settings->whatsapp)) {
            return;
        }

        $this->send($settings->whatsapp, 'admin_new_payment', $this->builder->orderTokens($order));
    }

    public function notifyCustomerInvoiceCreated(Invoice $invoice): void
    {
        $settings = StoreSetting::current();
        $order = $invoice->order;

        if (! $order || ! $this->canSend($settings, 'whatsapp_notify_customer_invoice') || blank($order->customer_phone)) {
            return;
        }

        $this->send($order->customer_phone, 'customer_invoice', $this->builder->invoiceTokens($invoice));
    }

    /** Sent when a shipment gets its tracking number. */
    public function notifyCustomerShipment(Shipment $shipment): void
    {
        $settings = StoreSetting::current();
        $order = $shipment->order;

        if (! $order || ! $this->canSend($settings, 'whatsapp_notify_customer_shipment') || blank($order->customer_phone)) {
            return;
        }

        $this->send($order->customer_phone, 'customer_shipment', $this->builder->shipmentTokens($shipment));
    }

    /** Renders the template and hands it to the gateway. */
    private function send(string $to, string $event, array $tokens): void
    {
        $message = $this->builder->build($event, 'whatsapp', $tokens);

        if (! $message) {
            return;
        }

        $this->gateway->send($to, $message['body'], $event, $this->logoUrl());
    }

    /**
     * The brand logo to attach, or null to send plain text.
     *
     * Providers fetch the image themselves, so a URL only reachable on the
     * development machine is left off rather than sent and rejected.
     */
    private function logoUrl(): ?string
    {
        $settings = StoreSetting::current();

        if (! $settings->whatsapp_send_logo) {
            return null;
        }

        if (! $settings->notificationLogoIsPubliclyReachable()) {
            Log::info('WhatsApp logo skipped: the image URL is not reachable from outside', [
                'url' => $settings->notificationLogoUrl(),
            ]);

            return null;
        }

        return $settings->notificationLogoUrl();
    }

    /** The channel is on, configured, and this particular event is enabled. */
    private function canSend(StoreSetting $settings, string $eventFlag): bool
    {
        return (bool) $settings->whatsapp_notifications_enabled
            && (bool) $settings->{$eventFlag}
            && $this->gateway->isConfigured($settings);
    }
}
