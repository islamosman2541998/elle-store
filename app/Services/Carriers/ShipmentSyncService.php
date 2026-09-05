<?php

namespace App\Services\Carriers;

use App\Models\Shipment;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies carrier outcomes to a shipment, and mirrors the important ones onto
 * the order so the shop owner and the customer see the same thing.
 */
class ShipmentSyncService
{
    /** Shipment status -> the order status it should drive. */
    private const ORDER_STATUS = [
        'picked_up' => 'shipped',
        'in_transit' => 'shipped',
        'delivered' => 'delivered',
        'returned' => 'returned',
    ];

    public function __construct(private readonly CarrierManager $carriers)
    {
    }

    /** Register the shipment with its carrier. */
    public function create(Shipment $shipment): CarrierResult
    {
        $company = $shipment->company;

        if (! $company) {
            return CarrierResult::failure(__('admin.carrier_not_configured'));
        }

        if (filled($shipment->carrier_shipment_id)) {
            return CarrierResult::failure(__('admin.carrier_already_created'));
        }

        $result = $this->carriers->driverFor($company)->createShipment($shipment);

        $this->apply($shipment, $result);

        return $result;
    }

    /** Pull the latest state from the carrier. */
    public function refresh(Shipment $shipment): CarrierResult
    {
        $company = $shipment->company;

        if (! $company) {
            return CarrierResult::failure(__('admin.carrier_not_configured'));
        }

        $result = $this->carriers->driverFor($company)->fetchStatus($shipment);

        $this->apply($shipment, $result);

        return $result;
    }

    public function cancel(Shipment $shipment): CarrierResult
    {
        $company = $shipment->company;

        if (! $company) {
            return CarrierResult::failure(__('admin.carrier_not_configured'));
        }

        $result = $this->carriers->driverFor($company)->cancelShipment($shipment);

        $this->apply($shipment, $result);

        return $result;
    }

    /**
     * Write a carrier result onto the shipment (and its order).
     *
     * Kept public so the webhook controller can reuse it.
     */
    public function apply(Shipment $shipment, CarrierResult $result): void
    {
        if (! $result->ok) {
            $shipment->forceFill([
                'carrier_error' => $result->error,
                'carrier_synced_at' => now(),
            ])->save();

            Log::warning('Carrier call failed', [
                'shipment' => $shipment->id,
                'error' => $result->error,
            ]);

            return;
        }

        DB::transaction(function () use ($shipment, $result): void {
            $updates = array_filter([
                'carrier_shipment_id' => $result->carrierShipmentId,
                'tracking_number' => $result->trackingNumber,
                'tracking_url' => $result->trackingUrl,
                'carrier_status' => $result->carrierStatus,
            ], fn ($value) => filled($value));

            // Rebuild the link if we learned an AWB but the carrier gave no URL.
            if (
                ! isset($updates['tracking_url'])
                && isset($updates['tracking_number'])
                && $shipment->company
            ) {
                $url = $shipment->company->generateTrackingUrl($updates['tracking_number']);

                if (filled($url)) {
                    $updates['tracking_url'] = $url;
                }
            }

            $updates['carrier_synced_at'] = now();
            $updates['carrier_error'] = null;
            $updates['carrier_payload'] = $result->payload ?: null;

            if (filled($result->shipmentStatus) && $result->shipmentStatus !== $shipment->status) {
                $updates['status'] = $result->shipmentStatus;
                $updates += $this->timestampFor($result->shipmentStatus);
            }

            $gainedTracking = isset($updates['tracking_number'])
                && $updates['tracking_number'] !== $shipment->getOriginal('tracking_number');

            $shipment->forceFill($updates)->save();

            $this->syncOrder($shipment->fresh(), $result->shipmentStatus);

            // Tell the customer as soon as there is something to track.
            if ($gainedTracking) {
                app(NotificationDispatcher::class)->customerShipment($shipment->fresh());
            }
        });
    }

    /** @return array<string, mixed> */
    private function timestampFor(string $status): array
    {
        return match ($status) {
            'assigned' => ['assigned_at' => now()],
            'picked_up' => ['picked_up_at' => now()],
            'in_transit' => ['in_transit_at' => now()],
            'delivered' => ['delivered_at' => now()],
            'failed' => ['failed_at' => now()],
            'returned' => ['returned_at' => now()],
            default => [],
        };
    }

    /**
     * Move the order along with the shipment.
     *
     * Stock is deliberately left alone here: it is deducted when the owner
     * confirms an order and restored on cancel/return, and a carrier scan must
     * not double up on that.
     */
    private function syncOrder(Shipment $shipment, ?string $shipmentStatus): void
    {
        $order = $shipment->order;

        if (! $order || blank($shipmentStatus)) {
            return;
        }

        $target = self::ORDER_STATUS[$shipmentStatus] ?? null;

        if ($target === null || $order->status === $target) {
            return;
        }

        // Never drag a finished order backwards.
        if (in_array($order->status, ['delivered', 'returned', 'cancelled'], true)) {
            return;
        }

        $from = $order->status;

        $order->update(array_filter([
            'status' => $target,
            'shipped_at' => $target === 'shipped' ? ($order->shipped_at ?? now()) : $order->shipped_at,
            'delivered_at' => $target === 'delivered' ? ($order->delivered_at ?? now()) : $order->delivered_at,
        ], fn ($value) => $value !== null));

        $order->statusHistories()->create([
            'user_id' => null,
            'from_status' => $from,
            'to_status' => $target,
            'note' => __('admin.carrier_status_note', [
                'carrier' => $shipment->company?->name ?? '-',
                'status' => $shipment->carrier_status ?? $shipmentStatus,
            ]),
        ]);
    }
}
