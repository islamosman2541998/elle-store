<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function createForOrder(Order $order): Invoice
    {
        if (! app(InvoiceSettingsService::class)->invoicesEnabled()) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices_are_disabled'),
            ]);
        }

        $existingInvoice = Invoice::query()
            ->where('order_id', $order->id)
            ->first();

        if ($existingInvoice) {
            return $existingInvoice;
        }

        $invoice = Invoice::query()->create([
            'invoice_number' => 'TEMP',
            'order_id' => $order->id,

            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,

            'status' => $order->payment_status === 'paid' ? 'paid' : 'unpaid',

            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'shipping_total' => (float) $order->shipping_total,
            'tax_total' => (float) $order->tax_total,
            'grand_total' => (float) $order->grand_total,

            'issued_at' => now(),
            'paid_at' => $order->payment_status === 'paid' ? now() : null,
            'cancelled_at' => null,

            'notes' => app(InvoiceSettingsService::class)->invoiceNotes(),
        ]);

        $invoice->update([
            'invoice_number' => app(InvoiceSettingsService::class)
                ->generateInvoiceNumber($invoice->id),
        ]);
        app(StoreNotificationService::class)->notifyCustomerInvoiceCreated($invoice->fresh());

        return $invoice;
    }
}