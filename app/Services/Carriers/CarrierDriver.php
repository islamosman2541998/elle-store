<?php

namespace App\Services\Carriers;

use App\Models\Shipment;

/**
 * What the store needs from a shipping carrier.
 *
 * Implementations must not throw for ordinary failures (network, rejected
 * payload, bad credentials) - they return a failed CarrierResult so an order
 * is never blocked by the carrier being unreachable.
 */
interface CarrierDriver
{
    /** Machine name stored in shipping_companies.integration_driver. */
    public static function key(): string;

    /** True when this driver can actually talk to the carrier. */
    public function isConfigured(): bool;

    /** Register the shipment with the carrier and return its AWB. */
    public function createShipment(Shipment $shipment): CarrierResult;

    /** Ask the carrier for the current state of a shipment. */
    public function fetchStatus(Shipment $shipment): CarrierResult;

    /** Ask the carrier to cancel a shipment that has not shipped yet. */
    public function cancelShipment(Shipment $shipment): CarrierResult;

    /**
     * Translate an incoming webhook body into a CarrierResult.
     *
     * Returns null when the payload is not something we act on.
     */
    public function parseWebhook(array $payload): ?CarrierResult;

    /** Verify a webhook really came from the carrier. */
    public function verifyWebhook(array $payload, array $headers, ?string $rawBody = null): bool;
}
