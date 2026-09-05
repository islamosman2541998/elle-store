<?php

namespace App\Services\Carriers;

/**
 * Outcome of one call to a carrier.
 *
 * Carrier calls must never break an order, so failures are returned rather
 * than thrown; the caller decides whether to surface or log them.
 */
class CarrierResult
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $carrierShipmentId = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $trackingUrl = null,
        /** Raw carrier state, e.g. "Delivered to consignee". */
        public readonly ?string $carrierStatus = null,
        /** Our own vocabulary: pending|assigned|picked_up|in_transit|delivered|failed|returned|cancelled */
        public readonly ?string $shipmentStatus = null,
        public readonly ?string $error = null,
        public readonly array $payload = [],
    ) {
    }

    public static function success(
        ?string $carrierShipmentId = null,
        ?string $trackingNumber = null,
        ?string $trackingUrl = null,
        ?string $carrierStatus = null,
        ?string $shipmentStatus = null,
        array $payload = [],
    ): self {
        return new self(
            ok: true,
            carrierShipmentId: $carrierShipmentId,
            trackingNumber: $trackingNumber,
            trackingUrl: $trackingUrl,
            carrierStatus: $carrierStatus,
            shipmentStatus: $shipmentStatus,
            payload: $payload,
        );
    }

    public static function failure(string $error, array $payload = []): self
    {
        return new self(ok: false, error: $error, payload: $payload);
    }
}
