<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\WishlistItem;

class GuestDataMergeService
{
    public function merge(int $customerId, string $guestSessionId): void
    {
        $this->mergeWishlist($customerId, $guestSessionId);
        $this->mergeCart($customerId, $guestSessionId);
    }

    private function mergeWishlist(int $customerId, string $guestSessionId): void
    {
        $guestItems = WishlistItem::query()
            ->whereNull('customer_id')
            ->where('session_id', $guestSessionId)
            ->get();

        foreach ($guestItems as $guestItem) {
            $exists = WishlistItem::query()
                ->where('customer_id', $customerId)
                ->where('product_id', $guestItem->product_id)
                ->exists();

            if ($exists) {
                $guestItem->delete();
                continue;
            }

            $guestItem->update([
                'customer_id' => $customerId,
                'session_id' => null,
            ]);
        }
    }

    private function mergeCart(int $customerId, string $guestSessionId): void
    {
        $guestCart = Cart::query()
            ->whereNull('customer_id')
            ->where('session_id', $guestSessionId)
            ->where('status', 'active')
            ->with('items')
            ->first();

        if (! $guestCart) {
            return;
        }

        $customerCart = Cart::query()->firstOrCreate(
            [
                'customer_id' => $customerId,
                'session_id' => $guestSessionId,
                'status' => 'active',
            ],
            [
                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'last_activity_at' => now(),
            ]
        );

        foreach ($guestCart->items as $guestItem) {
            $existingItem = CartItem::query()
                ->where('cart_id', $customerCart->id)
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existingItem) {
                $newQuantity = (int) $existingItem->quantity + (int) $guestItem->quantity;
                $unitPrice = (float) $existingItem->unit_price;

                $existingItem->update([
                    'quantity' => $newQuantity,
                    'subtotal' => $newQuantity * $unitPrice,
                ]);

                $guestItem->delete();

                continue;
            }

            $guestItem->update([
                'cart_id' => $customerCart->id,
            ]);
        }

        $this->recalculateCart($customerCart);

        $guestCart->items()->delete();
        $guestCart->delete();
    }

    private function recalculateCart(Cart $cart): void
    {
        $subtotal = (float) $cart->items()->sum('subtotal');

        $cart->update([
            'subtotal' => $subtotal,
            'grand_total' => $subtotal
                - (float) $cart->discount_total
                + (float) $cart->shipping_total
                + (float) $cart->tax_total,
            'last_activity_at' => now(),
        ]);
    }
}