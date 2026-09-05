<?php

namespace App\Services\Carriers;

use App\Models\Shipment;

/**
 * The default: no API at all.
 *
 * The shop creates the shipment in the carrier's own dashboard and types the
 * AWB in; the store only builds the tracking link from it. Every method fails
 * cleanly so callers can treat drivers uniformly.
 */
class ManualDriver implements CarrierDriver
{
    public static function key(): string
    {
        return 'manual';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function createShipment(Shipment $shipment): CarrierResult
    {
        return CarrierResult::failure(__('admin.carrier_manual_only'));
    }

    public function fetchStatus(Shipment $shipment): CarrierResult
    {
        return CarrierResult::failure(__('admin.carrier_manual_only'));
    }

    public function cancelShipment(Shipment $shipment): CarrierResult
    {
        return CarrierResult::failure(__('admin.carrier_manual_only'));
    }

    public function parseWebhook(array $payload): ?CarrierResult
    {
        return null;
    }

    public function verifyWebhook(array $payload, array $headers, ?string $rawBody = null): bool
    {
        return false;
    }
}
