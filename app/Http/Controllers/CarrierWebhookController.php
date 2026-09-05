<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShippingCompany;
use App\Services\Carriers\CarrierManager;
use App\Services\Carriers\ShipmentSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives delivery status pushes from a carrier.
 *
 * The URL carries the company id so one endpoint serves several carriers, and
 * every request must present that company's webhook secret - an unauthenticated
 * caller could otherwise mark orders as delivered.
 */
class CarrierWebhookController extends Controller
{
    public function __construct(
        private readonly CarrierManager $carriers,
        private readonly ShipmentSyncService $sync,
    ) {
    }

    public function __invoke(Request $request, ShippingCompany $company): JsonResponse
    {
        if (! $company->is_active || ! $company->usesApi()) {
            return response()->json(['message' => 'Carrier not accepting webhooks.'], 404);
        }

        $driver = $this->carriers->driverFor($company);
        $payload = (array) $request->json()->all();

        if (! $driver->verifyWebhook($payload, $request->headers->all(), $request->getContent())) {
            Log::warning('Rejected carrier webhook: bad signature', [
                'company' => $company->id,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $result = $driver->parseWebhook($payload);

        if ($result === null) {
            // Nothing actionable, but the carrier should not retry.
            return response()->json(['message' => 'Ignored.'], 200);
        }

        $shipment = $this->findShipment($company, $result->carrierShipmentId, $result->trackingNumber);

        if (! $shipment) {
            Log::info('Carrier webhook for an unknown shipment', [
                'company' => $company->id,
                'carrier_shipment_id' => $result->carrierShipmentId,
                'tracking_number' => $result->trackingNumber,
            ]);

            return response()->json(['message' => 'Shipment not found.'], 202);
        }

        $this->sync->apply($shipment, $result);

        return response()->json([
            'message' => 'Applied.',
            'shipment' => $shipment->shipment_number,
            'status' => $shipment->fresh()->status,
        ]);
    }

    private function findShipment(
        ShippingCompany $company,
        ?string $carrierShipmentId,
        ?string $trackingNumber,
    ): ?Shipment {
        return Shipment::query()
            ->where('shipping_company_id', $company->id)
            ->where(function ($query) use ($carrierShipmentId, $trackingNumber): void {
                if (filled($carrierShipmentId)) {
                    $query->orWhere('carrier_shipment_id', $carrierShipmentId);
                }

                if (filled($trackingNumber)) {
                    $query->orWhere('tracking_number', $trackingNumber);
                }
            })
            ->latest('id')
            ->first();
    }
}
