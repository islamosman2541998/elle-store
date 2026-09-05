<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function applyToOrder(Order $order, string $code): void
    {
        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => __('admin.coupon_not_found'),
            ]);
        }

        $subtotal = (float) $order->subtotal;

        $this->validateCoupon($coupon, $subtotal);

        $discountTotal = $coupon->calculateDiscount($subtotal);

        $shippingTotal = (float) $order->shipping_total;

        if ($coupon->free_shipping) {
            $shippingTotal = 0;
        }

        $grandTotal = $subtotal
            - $discountTotal
            + $shippingTotal
            + (float) $order->tax_total;

        $order->update([
            'coupon_code' => $coupon->code,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
            'grand_total' => max($grandTotal, 0),
        ]);

        $this->recalculateShippingIfNeeded($order->fresh());
    }

    public function removeFromOrder(Order $order): void
    {
        $subtotal = (float) $order->subtotal;

        $grandTotal = $subtotal
            + (float) $order->shipping_total
            + (float) $order->tax_total;

        $order->update([
            'coupon_code' => null,
            'discount_total' => 0,
            'grand_total' => max($grandTotal, 0),
        ]);

        $this->recalculateShippingIfNeeded($order->fresh());
    }

    public function markCouponAsUsedForOrder(Order $order): void
    {
        if (! $order->coupon_code) {
            return;
        }

        if ($order->coupon_used_at) {
            return;
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($order->coupon_code)))
            ->first();

        if (! $coupon) {
            return;
        }

        $coupon->increment('used_count');

        $order->update([
            'coupon_used_at' => now(),
        ]);
    }

    protected function validateCoupon(Coupon $coupon, float $subtotal): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => __('admin.coupon_is_inactive'),
            ]);
        }

        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            throw ValidationException::withMessages([
                'coupon_code' => __('admin.coupon_not_started_yet'),
            ]);
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            throw ValidationException::withMessages([
                'coupon_code' => __('admin.coupon_expired'),
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon_code' => __('admin.coupon_usage_limit_reached'),
            ]);
        }

        if (
            $coupon->minimum_order_amount !== null
            && $subtotal < (float) $coupon->minimum_order_amount
        ) {
            throw ValidationException::withMessages([
                'coupon_code' => __('admin.coupon_minimum_order_not_met') . ' ' . number_format((float) $coupon->minimum_order_amount, 2, '.', ',') . ' EGP',
            ]);
        }
    }

    protected function recalculateShippingIfNeeded(Order $order): void
    {
        if (! $order->shipping_city_id) {
            return;
        }

        app(OrderShippingService::class)->applyShippingToOrder(
            $order,
            (int) $order->shipping_city_id
        );
    }
}