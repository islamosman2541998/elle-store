<?php

namespace App\Services\Notifications;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\StoreNotificationService;
use App\Services\WhatsappNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point for every customer/admin notification.
 *
 * Email and WhatsApp are dispatched independently: WhatsApp used to be sent
 * from inside the email methods, so switching email off - or an SMTP failure -
 * silently killed WhatsApp too. Each channel is also wrapped, so a broken
 * provider can never take an order down with it.
 */
class NotificationDispatcher
{
    public function __construct(
        private readonly StoreNotificationService $mail,
        private readonly WhatsappNotificationService $whatsapp,
        private readonly MailConfigurator $mailConfig,
    ) {
    }

    public function adminNewOrder(Order $order): void
    {
        $this->fire('admin_new_order', $order->id,
            fn () => $this->mail->notifyAdminNewOrder($order),
            fn () => $this->whatsapp->notifyAdminNewOrder($order),
        );
    }

    public function customerNewOrder(Order $order): void
    {
        $this->fire('customer_new_order', $order->id,
            fn () => $this->mail->notifyCustomerNewOrder($order),
            fn () => $this->whatsapp->notifyCustomerNewOrder($order),
        );
    }

    public function adminNewPayment(Order $order): void
    {
        $this->fire('admin_new_payment', $order->id,
            fn () => $this->mail->notifyAdminNewPayment($order),
            fn () => $this->whatsapp->notifyAdminNewPayment($order),
        );
    }

    public function customerOrderStatus(Order $order): void
    {
        $this->fire('customer_order_status', $order->id,
            fn () => $this->mail->notifyCustomerOrderStatus($order),
            fn () => $this->whatsapp->notifyCustomerOrderStatus($order),
        );
    }

    public function customerInvoiceCreated(Invoice $invoice): void
    {
        $this->fire('customer_invoice', $invoice->id,
            fn () => $this->mail->notifyCustomerInvoiceCreated($invoice),
            fn () => $this->whatsapp->notifyCustomerInvoiceCreated($invoice),
        );
    }

    public function customerShipment(Shipment $shipment): void
    {
        $this->fire('customer_shipment', $shipment->id,
            fn () => $this->mail->notifyCustomerShipment($shipment),
            fn () => $this->whatsapp->notifyCustomerShipment($shipment),
        );
    }

    /** Run both channels, isolating failures. */
    private function fire(string $event, int|string $subject, callable $email, callable $whatsapp): void
    {
        $this->mailConfig->apply();

        foreach (['email' => $email, 'whatsapp' => $whatsapp] as $channel => $send) {
            try {
                $send();
            } catch (Throwable $e) {
                Log::warning('Notification channel failed', [
                    'event' => $event,
                    'channel' => $channel,
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
