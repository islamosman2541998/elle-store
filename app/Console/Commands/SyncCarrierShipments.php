<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Services\Carriers\ShipmentSyncService;
use Illuminate\Console\Command;

/**
 * Polls carriers for shipments that are still moving.
 *
 * Webhooks are the primary channel; this is the safety net for pushes that
 * were missed while the site was down, and for carriers with no webhooks.
 */
class SyncCarrierShipments extends Command
{
    protected $signature = 'carriers:sync
        {--limit=100 : How many shipments to refresh in one run}';

    protected $description = 'Refresh in-flight shipments from their carrier';

    /** Statuses that can still change. */
    private const OPEN = ['pending', 'assigned', 'picked_up', 'in_transit'];

    public function handle(ShipmentSyncService $sync): int
    {
        $shipments = Shipment::query()
            ->whereIn('status', self::OPEN)
            ->whereHas('company', fn ($query) => $query
                ->where('is_active', true)
                ->whereNotIn('integration_driver', ['manual']))
            ->where(fn ($query) => $query
                ->whereNotNull('carrier_shipment_id')
                ->orWhereNotNull('tracking_number'))
            ->with('company')
            ->orderBy('carrier_synced_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($shipments->isEmpty()) {
            $this->info('Nothing to sync.');

            return self::SUCCESS;
        }

        $updated = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            $before = $shipment->status;
            $result = $sync->refresh($shipment);

            if (! $result->ok) {
                $failed++;
                continue;
            }

            if ($shipment->fresh()->status !== $before) {
                $updated++;
                $this->line(sprintf(
                    '  %s: %s -> %s',
                    $shipment->shipment_number,
                    $before,
                    $shipment->fresh()->status
                ));
            }
        }

        $this->info(sprintf(
            'Checked %d, changed %d, failed %d.',
            $shipments->count(),
            $updated,
            $failed
        ));

        return self::SUCCESS;
    }
}
