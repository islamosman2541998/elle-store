<?php

namespace App\Services\Notifications;

use App\Models\Invoice;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\StoreSetting;

/**
 * Turns a stored template plus a model into the exact text that goes out.
 *
 * A template the shop has not edited falls back to the built-in wording, and a
 * template switched off returns null so the caller sends nothing.
 */
class MessageBuilder
{
    /**
     * @return array{subject: ?string, body: string, button: ?string}|null
     */
    public function build(string $event, string $channel, array $tokens, ?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();
        $template = NotificationTemplate::lookup($event, $channel);

        if ($template && ! $template->is_active) {
            return null;
        }

        $default = NotificationTemplates::default($event, $channel);

        $subject = $template?->subjectFor($locale) ?: ($locale === 'ar' ? $default['subject_ar'] : $default['subject_en']);
        $body = $template?->bodyFor($locale) ?: ($locale === 'ar' ? $default['body_ar'] : $default['body_en']);
        $button = $template?->buttonFor($locale) ?: ($locale === 'ar' ? $default['button_label_ar'] : $default['button_label_en']);

        if (blank($body)) {
            return null;
        }

        return [
            'subject' => $subject ? $this->substitute($subject, $tokens) : null,
            'body' => $this->replace($body, $tokens),
            'button' => $button ? $this->substitute($button, $tokens) : null,
        ];
    }

    /**
     * Fill in the placeholders of a whole message body.
     *
     * A line whose placeholders all came back empty is dropped: "رقم التتبع:"
     * with nothing after it reads like a bug, so it is left out instead. Lines
     * with no placeholders at all are always kept.
     */
    public function replace(string $text, array $tokens): string
    {
        // Never \R when splitting: outside UTF-8 mode it also matches the byte
        // 0x85, which is the tail of Arabic letters such as م, and shreds text.
        $lines = preg_split("/\r\n|\r|\n/", $text);

        $kept = [];

        foreach ($lines as $line) {
            if (! $this->lineIsEmptied($line, $tokens)) {
                $kept[] = $this->substitute($line, $tokens);
            }
        }

        // Collapse the runs of blank lines a dropped line can leave behind.
        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $kept)));
    }

    /** One-line values - a subject or a button - are filled in as they are. */
    public function substitute(string $text, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }

        return $text;
    }

    /** True when the line carried placeholders and every one of them is blank. */
    private function lineIsEmptied(string $line, array $tokens): bool
    {
        if (! preg_match_all('/\{([a-z_]+)\}/', $line, $matches)) {
            return false;
        }

        foreach ($matches[1] as $name) {
            // An unknown placeholder is left visible rather than silently
            // deleting the line it sits on.
            if (! array_key_exists($name, $tokens) || filled($tokens[$name])) {
                return false;
            }
        }

        return true;
    }

    // -------------------------------------------------------------- tokens

    public function orderTokens(Order $order): array
    {
        $settings = StoreSetting::current();
        $snapshot = $order->shipping_address_snapshot ?: [];

        return array_merge($this->storeTokens($settings), [
            'order_number' => $order->order_number,
            'order_url' => route('site.orders.show', ['orderNumber' => $order->order_number]),
            'order_status' => __('admin.order_' . $order->status),
            // Kept for templates written before the token was renamed.
            'status' => __('admin.order_' . $order->status),
            'order_date' => optional($order->created_at)->format('Y-m-d') ?? '',
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'items' => $this->itemLines($order),
            'items_count' => (string) $order->items()->sum('quantity'),
            'subtotal' => $this->money($order->subtotal),
            'discount' => $this->money($order->discount_total),
            'shipping' => $this->money($order->shipping_total),
            'total' => $this->money($order->grand_total),
            'payment_method' => $order->payment_method
                ? __('admin.payment_' . $order->payment_method)
                : '',
            'payment_status' => $order->payment_status
                ? __('admin.payment_' . $order->payment_status)
                : '',
            'coupon_code' => $order->coupon_code ?: '',
            'city' => $order->shippingCity?->name ?? ($snapshot['city'] ?? ''),
            'address' => $snapshot['address'] ?? '',
            'notes' => $order->customer_notes ?: '',
        ]);
    }

    public function invoiceTokens(Invoice $invoice): array
    {
        $base = $invoice->order
            ? $this->orderTokens($invoice->order)
            : $this->storeTokens(StoreSetting::current());

        return array_merge($base, [
            'invoice_number' => $invoice->invoice_number,
            'invoice_total' => $this->money($invoice->grand_total),
            'invoice_date' => optional($invoice->issued_at ?: $invoice->created_at)->format('Y-m-d') ?? '',
            'customer_name' => $invoice->customer_name ?: ($base['customer_name'] ?? ''),
        ]);
    }

    public function shipmentTokens(Shipment $shipment): array
    {
        $order = $shipment->order;
        $base = $order ? $this->orderTokens($order) : $this->storeTokens(StoreSetting::current());

        return array_merge($base, [
            'carrier' => $shipment->company?->name ?? '',
            'tracking_number' => $shipment->tracking_number ?: '',
            'tracking_url' => $shipment->tracking_url ?: ($base['order_url'] ?? ''),
        ]);
    }

    private function storeTokens(StoreSetting $settings): array
    {
        return [
            'store_name' => $settings->store_name ?: config('app.name'),
            'store_phone' => $settings->phone ?: '',
            'store_whatsapp' => $settings->whatsapp ?: '',
            'store_email' => $settings->email ?: '',
        ];
    }

    /** Sample values so the dashboard can preview a message without an order. */
    public function sampleTokens(): array
    {
        $settings = StoreSetting::current();

        return array_merge($this->storeTokens($settings), [
            'order_number' => 'ORD-10245',
            'order_url' => url('/order/ORD-10245'),
            'order_status' => __('admin.order_confirmed'),
            'status' => __('admin.order_confirmed'),
            'order_date' => now()->format('Y-m-d'),
            'customer_name' => __('admin.sample_customer_name'),
            'customer_phone' => '01022434418',
            'customer_email' => 'customer@example.com',
            'items' => '- ' . __('admin.sample_item_one') . " x1\n- " . __('admin.sample_item_two') . ' x2',
            'items_count' => '3',
            'subtotal' => $this->money(1450),
            'discount' => $this->money(145),
            'shipping' => $this->money(60),
            'total' => $this->money(1365),
            'payment_method' => __('admin.payment_cash_on_delivery'),
            'payment_status' => __('admin.payment_unpaid'),
            'coupon_code' => 'ELLE10',
            'city' => __('admin.sample_city'),
            'address' => __('admin.sample_address'),
            'notes' => '',
            'invoice_number' => 'INV-00087',
            'invoice_total' => $this->money(1365),
            'invoice_date' => now()->format('Y-m-d'),
            'carrier' => 'Bosta',
            'tracking_number' => 'BST-77412093',
            'tracking_url' => 'https://bosta.co/tracking-shipments?track=BST-77412093',
        ]);
    }

    private function itemLines(Order $order): string
    {
        return $order->items
            ->map(function ($item): string {
                $name = trim($item->product_name . ' ' . ($item->variant_name ?: ''));

                return '- ' . $name . ' x' . $item->quantity . ' — ' . $this->money($item->subtotal);
            })
            ->implode("\n");
    }

    private function money(float|string|null $amount): string
    {
        $symbol = StoreSetting::current()->currency_symbol ?: 'EGP';

        return number_format((float) $amount, 2, '.', ',') . ' ' . $symbol;
    }
}
