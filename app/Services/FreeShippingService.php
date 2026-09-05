<?php

namespace App\Services;

use App\Models\FreeShippingOffer;
use App\Models\ShippingCity;

class FreeShippingService
{
    public function getValidOffer(
        float $orderTotal,
        ?int $shippingCityId = null,
        ?int $shippingZoneId = null
    ): ?FreeShippingOffer {
        if ($shippingCityId && ! $shippingZoneId) {
            $city = ShippingCity::query()->find($shippingCityId);
            $shippingZoneId = $city?->shipping_zone_id;
        }

        return FreeShippingOffer::query()
            ->running()
            ->where(function ($query) use ($shippingCityId) {
                $query->whereNull('shipping_city_id');

                if ($shippingCityId) {
                    $query->orWhere('shipping_city_id', $shippingCityId);
                }
            })
            ->where(function ($query) use ($shippingZoneId) {
                $query->whereNull('shipping_zone_id');

                if ($shippingZoneId) {
                    $query->orWhere('shipping_zone_id', $shippingZoneId);
                }
            })
            ->where(function ($query) use ($orderTotal) {
                $query->whereNull('minimum_order_amount')
                    ->orWhere('minimum_order_amount', '<=', $orderTotal);
            })
            ->orderByDesc('priority')
            ->latest()
            ->first();
    }

    public function hasFreeShipping(
        float $orderTotal,
        ?int $shippingCityId = null,
        ?int $shippingZoneId = null
    ): bool {
        return (bool) $this->getValidOffer(
            orderTotal: $orderTotal,
            shippingCityId: $shippingCityId,
            shippingZoneId: $shippingZoneId
        );
    }
}