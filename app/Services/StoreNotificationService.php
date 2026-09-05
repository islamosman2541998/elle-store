<?php

namespace App\Services;

use App\Mail\StoreNotificationMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Notifications\MessageBuilder;
use Illuminate\Support\Facades\Mail;

/**
 * Email notifications for order events.
 *
 * The wording lives in the notification_templates table and is edited from the
 * dashboard; this class only decides whether to send and to whom.
 */
class StoreNotificationService
{
    public function __construct(private readonly MessageBuilder $builder)
    {
    }

    public function notifyAdminNewOrder(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if (! $settings->shouldNotifyAdminNewOrder() || ! $settings->adminEmail()) {
            return;
        }

        $this->send(
            $settings->adminEmail(),
            'admin_new_order',
            $this->builder->orderTokens($order)
        );
    }

    public function notifyCustomerNewOrder(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if (! $settings->shouldNotifyCustomerNewOrder() || ! $order->customer_email) {
            return;
        }

        $this->send(
            $order->customer_email,
            'customer_new_order',
            $this->builder->orderTokens($order)
        );
    }

    public function notifyAdminNewPayment(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if (! $settings->shouldNotifyAdminNewPayment() || ! $settings->adminEmail()) {
            return;
        }

        $this->send(
            $settings->adminEmail(),
            'admin_new_payment',
            $this->builder->orderTokens($order)
        );
    }

    public function notifyCustomerOrderStatus(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if (! $settings->shouldNotifyCustomerOrderStatus() || ! $order->customer_email) {
            return;
        }

        $this->send(
            $order->customer_email,
            'customer_order_status',
            $this->builder->orderTokens($order)
        );
    }

    public function notifyCustomerInvoiceCreated(Invoice $invoice): void
    {
        $settings = app(NotificationSettingsService::class);

        if (! $settings->shouldNotifyCustomerInvoiceCreated() || ! $invoice->customer_email) {
            return;
        }

        $this->send(
            $invoice->customer_email,
            'customer_invoice',
            $this->builder->invoiceTokens($invoice)
        );
    }

    /** Tells the customer their parcel is moving, with the tracking link. */
    public function notifyCustomerShipment(Shipment $shipment): void
    {
        $settings = app(NotificationSettingsService::class);
        $order = $shipment->order;

        if (! $order || ! $settings->shouldNotifyCustomerShipment() || ! $order->customer_email) {
            return;
        }

        $tokens = $this->builder->shipmentTokens($shipment);

        $this->send($order->customer_email, 'customer_shipment', $tokens, $tokens['tracking_url'] ?? null);
    }

    /** Renders the template and posts it, unless the template is switched off. */
    private function send(string $to, string $event, array $tokens, ?string $actionUrl = null): void
    {
        $message = $this->builder->build($event, 'email', $tokens);

        if (! $message) {
            return;
        }

        Mail::to($to)->send(new StoreNotificationMail(
            $message['subject'] ?: ($tokens['store_name'] ?? config('app.name')),
            $message['body'],
            $message['button'] ? ($actionUrl ?: ($tokens['order_url'] ?? null)) : null,
            $message['button'],
        ));
    }
}
