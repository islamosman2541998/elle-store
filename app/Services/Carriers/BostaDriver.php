<?php

namespace App\Services\Carriers;

use App\Models\Shipment;
use App\Models\ShippingCompany;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bosta (Egypt) integration.
 *
 * Endpoints, field names and state names live in config/carriers.php rather
 * than being hard-coded, because Bosta can change them and the shop owner
 * should be able to correct a mapping without a code change.
 *
 * Everything here fails soft: a carrier outage must never stop an order.
 */
class BostaDriver implements CarrierDriver
{
    public function __construct(private readonly ShippingCompany $company)
    {
    }

    public static function key(): string
    {
        return 'bosta';
    }

    public function isConfigured(): bool
    {
        return filled($this->company->api_key) && filled($this->baseUrl());
    }

    public function createShipment(Shipment $shipment): CarrierResult
    {
        if (! $this->isConfigured()) {
            return CarrierResult::failure(__('admin.carrier_not_configured'));
        }

        $order = $shipment->order;

        if (! $order) {
            return CarrierResult::failure('Shipment has no order.');
        }

        $payload = $this->buildDeliveryPayload($shipment);

        $response = $this->request()->post($this->endpoint('create_delivery'), $payload);

        if ($response === null) {
            return CarrierResult::failure(__('admin.carrier_unreachable'));
        }

        if ($response->failed()) {
            return CarrierResult::failure(
                $this->errorMessage($response->json(), $response->status()),
                ['request' => $payload, 'response' => $response->json()]
            );
        }

        $body = (array) $response->json();
        $data = (array) data_get($body, config('carriers.bosta.response.data_path'), $body);

        $trackingNumber = $this->firstFilled($data, config('carriers.bosta.response.tracking_number'));
        $carrierId = $this->firstFilled($data, config('carriers.bosta.response.shipment_id'));

        if (blank($trackingNumber)) {
            return CarrierResult::failure(
                __('admin.carrier_no_tracking_number'),
                ['request' => $payload, 'response' => $body]
            );
        }

        return CarrierResult::success(
            carrierShipmentId: $carrierId,
            trackingNumber: $trackingNumber,
            trackingUrl: $this->company->generateTrackingUrl($trackingNumber),
            carrierStatus: $this->firstFilled($data, config('carriers.bosta.response.state')),
            shipmentStatus: 'assigned',
            payload: ['request' => $payload, 'response' => $body],
        );
    }

    public function fetchStatus(Shipment $shipment): CarrierResult
    {
        if (! $this->isConfigured()) {
            return CarrierResult::failure(__('admin.carrier_not_configured'));
        }

        $reference = $shipment->carrier_shipment_id ?: $shipment->tracking_number;

        if (blank($reference)) {
            return CarrierResult::failure(__('admin.carrier_missing_reference'));
        }

        $endpoint = str_replace(
            '{reference}',
            rawurlencode($reference),
            $this->endpoint('track_delivery')
        );

        $response = $this->request()->get($endpoint);

        if ($response === null) {
            return CarrierResult::failure(__('admin.carrier_unreachable'));
        }

        if ($response->failed()) {
            return CarrierResult::failure(
                $this->errorMessage($response->json(), $response->status()),
                ['response' => $response->json()]
            );
        }

        $body = (array) $response->json();
        $data = (array) data_get($body, config('carriers.bosta.response.data_path'), $body);

        $state = $this->firstFilled($data, config('carriers.bosta.response.state'));

        return CarrierResult::success(
            carrierShipmentId: $this->firstFilled($data, config('carriers.bosta.response.shipment_id'))
                ?: $shipment->carrier_shipment_id,
            trackingNumber: $this->firstFilled($data, config('carriers.bosta.response.tracking_number'))
                ?: $shipment->tracking_number,
            carrierStatus: $state,
            shipmentStatus: $this->mapState($state),
            payload: ['response' => $body],
        );
    }

    public function cancelShipment(Shipment $shipment): CarrierResult
    {
        if (! $this->isConfigured()) {
            return CarrierResult::failure(__('admin.carrier_not_configured'));
        }

        if (blank($shipment->carrier_shipment_id)) {
            return CarrierResult::failure(__('admin.carrier_missing_reference'));
        }

        $endpoint = str_replace(
            '{reference}',
            rawurlencode($shipment->carrier_shipment_id),
            $this->endpoint('cancel_delivery')
        );

        $response = $this->request()->delete($endpoint);

        if ($response === null) {
            return CarrierResult::failure(__('admin.carrier_unreachable'));
        }

        if ($response->failed()) {
            return CarrierResult::failure(
                $this->errorMessage($response->json(), $response->status()),
                ['response' => $response->json()]
            );
        }

        return CarrierResult::success(
            carrierShipmentId: $shipment->carrier_shipment_id,
            shipmentStatus: 'cancelled',
            payload: ['response' => (array) $response->json()],
        );
    }

    public function parseWebhook(array $payload): ?CarrierResult
    {
        $data = (array) data_get($payload, config('carriers.bosta.webhook.data_path'), $payload);

        $trackingNumber = $this->firstFilled($data, config('carriers.bosta.response.tracking_number'));
        $carrierId = $this->firstFilled($data, config('carriers.bosta.response.shipment_id'));

        if (blank($trackingNumber) && blank($carrierId)) {
            return null;
        }

        $state = $this->firstFilled($data, config('carriers.bosta.response.state'));

        return CarrierResult::success(
            carrierShipmentId: $carrierId,
            trackingNumber: $trackingNumber,
            carrierStatus: $state,
            shipmentStatus: $this->mapState($state),
            payload: $payload,
        );
    }

    public function verifyWebhook(array $payload, array $headers, ?string $rawBody = null): bool
    {
        $secret = $this->company->webhook_secret;

        // No secret configured means the endpoint is unauthenticated; refuse
        // rather than trusting anonymous status changes.
        if (blank($secret)) {
            return false;
        }

        $headers = array_change_key_case($headers, CASE_LOWER);
        $headerName = strtolower((string) config('carriers.bosta.webhook.signature_header'));

        $provided = $headers[$headerName] ?? null;
        $provided = is_array($provided) ? ($provided[0] ?? null) : $provided;

        if (blank($provided)) {
            return false;
        }

        // Accept either a shared secret or an HMAC of the raw body, so the
        // integration works with whichever Bosta enables for the account.
        if (hash_equals($secret, (string) $provided)) {
            return true;
        }

        if ($rawBody !== null) {
            $expected = hash_hmac('sha256', $rawBody, $secret);

            if (hash_equals($expected, (string) $provided)) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------------------------------- internals

    private function buildDeliveryPayload(Shipment $shipment): array
    {
        $order = $shipment->order;
        $address = (array) ($shipment->shipping_address_snapshot ?: $order->shipping_address_snapshot ?: []);

        [$firstName, $lastName] = $this->splitName((string) $order->customer_name);

        // Cash to collect: only for cash-on-delivery orders that are not paid.
        $cod = ($order->payment_method === 'cash_on_delivery' && $order->payment_status !== 'paid')
            ? (float) $order->grand_total
            : 0;

        return [
            'type' => (int) config('carriers.bosta.delivery_type'),
            'businessReference' => $order->order_number,
            'cod' => $cod,
            'notes' => (string) ($shipment->notes ?? ''),
            'specs' => [
                'packageType' => config('carriers.bosta.package_type'),
                'packageDetails' => [
                    'itemsCount' => (int) max(1, $order->items()->sum('quantity')),
                    'description' => $this->itemsDescription($order),
                ],
            ],
            'receiver' => array_filter([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phone' => $this->normalisePhone((string) $order->customer_phone),
                'email' => $order->customer_email,
            ]),
            'dropOffAddress' => array_filter([
                'city' => $shipment->city?->name_en ?: ($address['city'] ?? null),
                'firstLine' => $address['address'] ?? ($address['firstLine'] ?? '-'),
                'secondLine' => $address['region'] ?? null,
            ]),
            'pickupAddress' => array_filter([
                'locationId' => $this->company->pickup_location_id,
            ]),
        ];
    }

    private function itemsDescription($order): string
    {
        return $order->items
            ->take(5)
            ->map(fn ($item) => trim(($item->product_name ?? '') . ' x' . $item->quantity))
            ->filter()
            ->implode(', ') ?: 'Order ' . $order->order_number;
    }

    /** @return array{0: string, 1: string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        $first = $parts[0] ?? 'Customer';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '-';

        return [$first, $last];
    }

    /** Bosta expects local Egyptian numbers such as 01012345678. */
    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '20')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function mapState(?string $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $map = (array) config('carriers.bosta.state_map', []);
        $needle = mb_strtolower(trim($state));

        foreach ($map as $carrierState => $ours) {
            if (mb_strtolower((string) $carrierState) === $needle) {
                return $ours;
            }
        }

        // Fall back to a loose contains match so wording changes ("Delivered"
        // vs "Delivered to consignee") do not silently stop updates.
        foreach ($map as $carrierState => $ours) {
            if (str_contains($needle, mb_strtolower((string) $carrierState))) {
                return $ours;
            }
        }

        return null;
    }

    private function baseUrl(): string
    {
        return rtrim(
            $this->company->api_base_url
                ?: (string) config('carriers.bosta.' . ($this->company->sandbox_mode ? 'sandbox_url' : 'base_url')),
            '/'
        );
    }

    private function endpoint(string $name): string
    {
        return $this->baseUrl() . '/' . ltrim((string) config('carriers.bosta.endpoints.' . $name), '/');
    }

    private function request(): CarrierHttp
    {
        $header = (string) config('carriers.bosta.auth_header', 'Authorization');
        $prefix = (string) config('carriers.bosta.auth_prefix', '');

        $client = Http::timeout((int) config('carriers.timeout', 20))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                $header => trim($prefix . ' ' . $this->company->api_key),
            ]);

        return new CarrierHttp($client, $this->company);
    }

    private function firstFilled(array $data, array|string|null $paths): ?string
    {
        foreach ((array) $paths as $path) {
            $value = data_get($data, $path);

            if (filled($value) && (is_string($value) || is_numeric($value))) {
                return (string) $value;
            }
        }

        return null;
    }

    private function errorMessage(mixed $body, int $status): string
    {
        $message = data_get($body, 'message')
            ?? data_get($body, 'error')
            ?? data_get($body, 'errors.0.message');

        if (is_array($message)) {
            $message = implode(', ', array_map('strval', $message));
        }

        return trim('HTTP ' . $status . ' ' . (is_string($message) ? $message : ''));
    }
}
