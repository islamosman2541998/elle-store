<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShippingCity;
use App\Models\StoreSetting;

class OrderShippingService
{
    public function applyShippingToOrder(Order $order, ?int $shippingCityId): void
    {
        $order = $order->fresh();

        $settings = StoreSetting::current();

        if (! $settings->shipping_enabled) {
            $order->update([
                'shipping_city_id' => $shippingCityId,
                'shipping_zone_id' => null,
                'shipping_total' => 0,
                'shipping_discount_source' => 'shipping_disabled',
                'free_shipping_offer_id' => null,
                'grand_total' => $this->calculateGrandTotal($order, 0),
            ]);

            return;
        }

        if (! $shippingCityId) {
            $order->update([
                'shipping_city_id' => null,
                'shipping_zone_id' => null,
                'shipping_total' => 0,
                'shipping_discount_source' => null,
                'free_shipping_offer_id' => null,
                'grand_total' => $this->calculateGrandTotal($order, 0),
            ]);

            return;
        }

        $city = ShippingCity::query()
            ->with('zone')
            ->find($shippingCityId);

        if (! $city) {
            return;
        }

        $subtotal = (float) $order->subtotal;

        $shippingFee = $city->calculateDeliveryFee($subtotal);

        if (
            $settings->global_free_shipping_enabled
            && (
                $settings->global_free_shipping_minimum === null
                || $subtotal >= (float) $settings->global_free_shipping_minimum
            )
        ) {
            $shippingFee = 0;

            $order->update([
                'shipping_city_id' => $city->id,
                'shipping_zone_id' => $city->shipping_zone_id,
                'shipping_total' => $shippingFee,
                'shipping_discount_source' => 'global_free_shipping',
                'free_shipping_offer_id' => null,
                'grand_total' => $this->calculateGrandTotal($order, $shippingFee),
            ]);

            return;
        }

        $shippingDiscountSource = null;
        $freeShippingOfferId = null;

        if ($this->orderCouponGivesFreeShipping($order)) {
            $shippingFee = 0;
            $shippingDiscountSource = 'coupon_free_shipping';
        } else {
            $freeShippingOffer = app(FreeShippingService::class)->getValidOffer(
                orderTotal: $subtotal,
                shippingCityId: $city->id,
                shippingZoneId: $city->shipping_zone_id
            );

            if ($freeShippingOffer) {
                $shippingFee = 0;
                $shippingDiscountSource = 'free_shipping_offer';
                $freeShippingOfferId = $freeShippingOffer->id;
            }
        }

        $order->update([
            'shipping_city_id' => $city->id,
            'shipping_zone_id' => $city->shipping_zone_id,
            'shipping_total' => $shippingFee,
            'shipping_discount_source' => $shippingDiscountSource,
            'free_shipping_offer_id' => $freeShippingOfferId,
            'grand_total' => $this->calculateGrandTotal($order, $shippingFee),
        ]);
    }

    protected function calculateGrandTotal(Order $order, float $shippingFee): float
    {
        $grandTotal = (float) $order->subtotal
            - (float) $order->discount_total
            + $shippingFee
            + (float) $order->tax_total
            + (float) $order->payment_fee;

        return max($grandTotal, 0);
    }

    protected function orderCouponGivesFreeShipping(Order $order): bool
    {
        if (! $order->coupon_code) {
            return false;
        }

        return \App\Models\Coupon::query()
            ->where('code', strtoupper(trim($order->coupon_code)))
            ->where('is_active', true)
            ->where('free_shipping', true)
            ->exists();
    }
}