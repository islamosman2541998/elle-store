<?php

namespace App\Services;

use App\Mail\StoreNotificationMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Notifications\MessageBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email notifications for order events.
 *
 * The wording lives in the notification_templates table and is edited from the
 * dashboard; this class only decides whether to send and to whom.
 *
 * Every decision is logged. A message that is never sent used to look exactly
 * like one that was sent and lost in transit, which made "no email arrived"
 * impossible to answer - so a skip says why, and a send says where it went.
 */
class StoreNotificationService
{
    public function __construct(private readonly MessageBuilder $builder)
    {
    }

    public function notifyAdminNewOrder(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if ($this->skip('admin_new_order', match (true) {
            ! $settings->emailEnabled() => 'email notifications are off',
            ! $settings->settings()->notify_admin_new_order => 'this event is off',
            blank($settings->adminEmail()) => 'no admin notification address',
            default => null,
        })) {
            return;
        }

        $this->send($settings->adminEmail(), 'admin_new_order', $this->builder->orderTokens($order));
    }

    public function notifyCustomerNewOrder(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if ($this->skip('customer_new_order', match (true) {
            ! $settings->emailEnabled() => 'email notifications are off',
            ! $settings->settings()->notify_customer_new_order => 'this event is off',
            blank($order->customer_email) => 'the order has no customer email',
            default => null,
        })) {
            return;
        }

        $this->send($order->customer_email, 'customer_new_order', $this->builder->orderTokens($order));
    }

    public function notifyAdminNewPayment(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if ($this->skip('admin_new_payment', match (true) {
            ! $settings->emailEnabled() => 'email notifications are off',
            ! $settings->settings()->notify_admin_new_payment => 'this event is off',
            blank($settings->adminEmail()) => 'no admin notification address',
            default => null,
        })) {
            return;
        }

        $this->send($settings->adminEmail(), 'admin_new_payment', $this->builder->orderTokens($order));
    }

    public function notifyCustomerOrderStatus(Order $order): void
    {
        $settings = app(NotificationSettingsService::class);

        if ($this->skip('customer_order_status', match (true) {
            ! $settings->emailEnabled() => 'email notifications are off',
            ! $settings->settings()->notify_customer_order_status => 'this event is off',
            blank($order->customer_email) => 'the order has no customer email',
            default => null,
        })) {
            return;
        }

        $this->send($order->customer_email, 'customer_order_status', $this->builder->orderTokens($order));
    }

    public function notifyCustomerInvoiceCreated(Invoice $invoice): void
    {
        $settings = app(NotificationSettingsService::class);

        if ($this->skip('customer_invoice', match (true) {
            ! $settings->emailEnabled() => 'email notifications are off',
            ! $settings->settings()->notify_customer_invoice_created => 'this event is off',
            blank($invoice->customer_email) => 'the invoice has no customer email',
            default => null,
        })) {
            return;
        }

        $this->send($invoice->customer_email, 'customer_invoice', $this->builder->invoiceTokens($invoice));
    }

    /** Tells the customer their parcel is moving, with the tracking link. */
    public function notifyCustomerShipment(Shipment $shipment): void
    {
        $settings = app(NotificationSettingsService::class);
        $order = $shipment->order;

        if ($this->skip('customer_shipment', match (true) {
            ! $order => 'the shipment has no order',
            ! $settings->emailEnabled() => 'email notifications are off',
            ! $settings->settings()->notify_customer_shipment => 'this event is off',
            blank($order->customer_email) => 'the order has no customer email',
            default => null,
        })) {
            return;
        }

        $tokens = $this->builder->shipmentTokens($shipment);

        $this->send($order->customer_email, 'customer_shipment', $tokens, $tokens['tracking_url'] ?? null);
    }

    /** Records why a message was not sent, and reports whether to stop. */
    private function skip(string $event, ?string $reason): bool
    {
        if ($reason === null) {
            return false;
        }

        Log::info('Email notification skipped', [
            'event' => $event,
            'reason' => $reason,
        ]);

        return true;
    }

    /** Renders the template and posts it, unless the template is switched off. */
    private function send(string $to, string $event, array $tokens, ?string $actionUrl = null): void
    {
        $message = $this->builder->build($event, 'email', $tokens);

        if (! $message) {
            $this->skip($event, 'the message template is switched off or empty');

            return;
        }

        $sent = Mail::to($to)->send(new StoreNotificationMail(
            $message['subject'] ?: ($tokens['store_name'] ?? config('app.name')),
            $message['body'],
            $message['button'] ? ($actionUrl ?: ($tokens['order_url'] ?? null)) : null,
            $message['button'],
        ));

        // The message id is what the mail server logs too, so a message that
        // is accepted here and never arrives can be traced on the host.
        Log::info('Email notification accepted by the mail server', [
            'event' => $event,
            'to' => $to,
            'from' => config('mail.from.address'),
            'mailer' => config('mail.default'),
            'message_id' => $sent?->getMessageId(),
        ]);
    }
}
