<?php

namespace App\Services;

use App\Models\StoreSetting;
use Illuminate\Support\Str;

class TrackingEventService
{
    public function isEnabled(): bool
    {
        return (bool) StoreSetting::current()->tracking_enabled;
    }

    public function eventId(string $prefix = 'event'): string
    {
        return $prefix . '_' . Str::uuid()->toString();
    }

    public function productPayload($product, ?array $pricing = null, int $quantity = 1): array
    {
        $price = $pricing['final_price'] ?? $product->price ?? 0;

        return [
            'content_ids' => [(string) $product->id],
            'content_type' => 'product',
            'content_name' => $product->name ?? $product->arabicTranslation?->name ?? $product->englishTranslation?->name ?? 'Product #' . $product->id,
            'currency' => StoreSetting::current()->currency_code ?? 'EGP',
            'value' => round((float) $price * $quantity, 2),
            'quantity' => $quantity,
        ];
    }

    public function cartPayload($cart): array
    {
        $cart->loadMissing('items.product');

        $contentIds = $cart->items
            ->pluck('product_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->toArray();

        $total = $cart->items->sum(fn($item) => (float) $item->subtotal);

        return [
            'content_ids' => $contentIds,
            'content_type' => 'product',
            'num_items' => $cart->items->sum(fn($item) => (int) $item->quantity),
            'currency' => StoreSetting::current()->currency_code ?? 'EGP',
            'value' => round((float) $total, 2),
        ];
    }

    public function orderPayload($order): array
    {
        $order->loadMissing('items');

        $contentIds = $order->items
            ->pluck('product_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->toArray();

        return [
            'content_ids' => $contentIds,
            'content_type' => 'product',
            'num_items' => $order->items->sum(fn($item) => (int) $item->quantity),
            'currency' => StoreSetting::current()->currency_code ?? 'EGP',
            'value' => round((float) $order->grand_total, 2),
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
        ];
    }

    public function searchPayload(string $query): array
    {
        return [
            'search_string' => $query,
        ];
    }
}