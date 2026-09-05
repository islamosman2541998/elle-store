<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingCity;
use App\Models\ShippingCompany;
use App\Services\Carriers\CarrierManager;
use App\Services\Carriers\ShipmentSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Bosta integration, exercised against a faked HTTP layer.
 *
 * Nothing here touches the real carrier: every request is intercepted, so the
 * assertions are about the payload we send and how we react to what comes
 * back.
 */
class CarrierIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private function carrier(array $overrides = []): ShippingCompany
    {
        return ShippingCompany::query()->create(array_merge([
            'name' => 'Bosta',
            'code' => 'bosta-test',
            'integration_driver' => 'bosta',
            'api_base_url' => 'https://stg-app.bosta.co/api/v2',
            'api_key' => 'test-api-key',
            'webhook_secret' => 'test-webhook-secret',
            'pickup_location_id' => 'pickup-1',
            'tracking_url_template' => 'https://bosta.co/tracking-shipments?tracking_number={tracking_number}',
            // Off by default so each test decides what triggers a call.
            'auto_create_shipment' => false,
            'sandbox_mode' => true,
            'is_active' => true,
            'sort_order' => 99,
        ], $overrides));
    }

    private function shipmentFor(ShippingCompany $company): Shipment
    {
        $order = Order::query()->where('status', '!=', 'cancelled')->latest('id')->firstOrFail();
        $city = ShippingCity::query()->where('is_active', true)->firstOrFail();

        return Shipment::query()->create([
            'shipment_number' => 'SHP-TEST-' . uniqid(),
            'order_id' => $order->id,
            'shipping_company_id' => $company->id,
            'shipping_city_id' => $city->id,
            'status' => 'pending',
            'shipping_fee' => $order->shipping_total,
            'shipping_address_snapshot' => ['address' => '12 Test St', 'city' => $city->name_ar],
        ]);
    }

    public function test_creating_a_shipment_sends_the_expected_payload_and_stores_the_awb(): void
    {
        Http::fake([
            '*/deliveries' => Http::response([
                'data' => ['_id' => 'bosta-abc-123', 'trackingNumber' => '9876543', 'state' => ['value' => 'Created']],
            ], 201),
        ]);

        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);

        $result = app(ShipmentSyncService::class)->create($shipment);

        $this->assertTrue($result->ok, 'the call should succeed: ' . ($result->error ?? ''));

        $shipment->refresh();
        $this->assertSame('bosta-abc-123', $shipment->carrier_shipment_id);
        $this->assertSame('9876543', $shipment->tracking_number);
        $this->assertSame(
            'https://bosta.co/tracking-shipments?tracking_number=9876543',
            $shipment->tracking_url,
            'the tracking link is built from the carrier template'
        );
        $this->assertSame('assigned', $shipment->status);
        $this->assertNotNull($shipment->carrier_synced_at);
        $this->assertNull($shipment->carrier_error);

        Http::assertSent(function ($request) use ($shipment) {
            $body = $request->data();

            return str_contains($request->url(), '/deliveries')
                && $request->hasHeader('Authorization', 'test-api-key')
                && $body['businessReference'] === $shipment->order->order_number
                && isset($body['receiver']['phone'])
                && isset($body['dropOffAddress']['city']);
        });
    }

    public function test_cash_on_delivery_orders_send_the_amount_to_collect(): void
    {
        Http::fake(['*' => Http::response(['data' => ['_id' => 'x', 'trackingNumber' => '1']], 201)]);

        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);

        $shipment->order->update(['payment_method' => 'cash_on_delivery', 'payment_status' => 'unpaid']);

        app(ShipmentSyncService::class)->create($shipment->fresh());

        Http::assertSent(fn ($request) => (float) $request->data()['cod'] > 0);
    }

    public function test_an_already_paid_order_asks_the_carrier_to_collect_nothing(): void
    {
        Http::fake(['*' => Http::response(['data' => ['_id' => 'x', 'trackingNumber' => '2']], 201)]);

        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);

        $shipment->order->update(['payment_method' => 'cash_on_delivery', 'payment_status' => 'paid']);

        app(ShipmentSyncService::class)->create($shipment->fresh());

        Http::assertSent(fn ($request) => (float) $request->data()['cod'] === 0.0);
    }

    public function test_a_carrier_error_is_recorded_without_breaking_the_shipment(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid pickup location'], 422)]);

        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);

        $result = app(ShipmentSyncService::class)->create($shipment);

        $this->assertFalse($result->ok);

        $shipment->refresh();
        $this->assertStringContainsString('Invalid pickup location', (string) $shipment->carrier_error);
        $this->assertSame('pending', $shipment->status, 'a failed call must not advance the shipment');
        $this->assertNull($shipment->tracking_number);
    }

    public function test_a_network_failure_is_reported_not_thrown(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);

        $result = app(ShipmentSyncService::class)->create($shipment);

        $this->assertFalse($result->ok);
        $this->assertNotNull($shipment->fresh()->carrier_error);
    }

    public function test_a_company_without_an_api_key_is_not_configured(): void
    {
        $company = $this->carrier(['api_key' => null]);

        $this->assertFalse(app(CarrierManager::class)->driverFor($company)->isConfigured());

        $result = app(ShipmentSyncService::class)->create($this->shipmentFor($company));

        $this->assertFalse($result->ok);
    }

    public function test_refreshing_maps_the_carrier_state_and_moves_the_order(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-1', 'tracking_number' => '555']);

        $order = $shipment->order;
        $order->update(['status' => 'processing']);

        Http::fake(['*' => Http::response([
            'data' => ['_id' => 'bosta-1', 'trackingNumber' => '555', 'state' => ['value' => 'Out for delivery']],
        ])]);

        app(ShipmentSyncService::class)->refresh($shipment);

        $shipment->refresh();
        $this->assertSame('in_transit', $shipment->status);
        $this->assertSame('Out for delivery', $shipment->carrier_status);

        $this->assertSame('shipped', $order->fresh()->status, 'the order follows the shipment');
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'shipped',
        ]);
    }

    public function test_delivery_marks_the_order_delivered(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-2']);
        $shipment->order->update(['status' => 'shipped']);

        Http::fake(['*' => Http::response([
            'data' => ['_id' => 'bosta-2', 'trackingNumber' => '556', 'state' => ['value' => 'Delivered to consignee']],
        ])]);

        app(ShipmentSyncService::class)->refresh($shipment);

        $this->assertSame('delivered', $shipment->fresh()->status);
        $this->assertSame('delivered', $shipment->order->fresh()->status);
        $this->assertNotNull($shipment->fresh()->delivered_at);
    }

    public function test_a_finished_order_is_never_dragged_backwards(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-3']);
        $shipment->order->update(['status' => 'cancelled']);

        Http::fake(['*' => Http::response([
            'data' => ['_id' => 'bosta-3', 'trackingNumber' => '557', 'state' => ['value' => 'In transit']],
        ])]);

        app(ShipmentSyncService::class)->refresh($shipment);

        $this->assertSame('cancelled', $shipment->order->fresh()->status);
    }

    // ------------------------------------------------------------- webhooks

    public function test_a_signed_webhook_updates_the_shipment(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-hook', 'tracking_number' => '777']);
        $shipment->order->update(['status' => 'processing']);

        $response = $this->withHeaders(['X-Bosta-Signature' => 'test-webhook-secret'])
            ->postJson('/api/carriers/' . $company->id . '/webhook', [
                'data' => ['_id' => 'bosta-hook', 'trackingNumber' => '777', 'state' => ['value' => 'Delivered']],
            ]);

        $response->assertOk();

        $this->assertSame('delivered', $shipment->fresh()->status);
        $this->assertSame('delivered', $shipment->order->fresh()->status);
    }

    public function test_an_hmac_signed_webhook_is_also_accepted(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-hmac']);

        $payload = ['data' => ['_id' => 'bosta-hmac', 'trackingNumber' => '778', 'state' => ['value' => 'Picked up']]];
        $signature = hash_hmac('sha256', json_encode($payload), 'test-webhook-secret');

        $this->withHeaders(['X-Bosta-Signature' => $signature])
            ->postJson('/api/carriers/' . $company->id . '/webhook', $payload)
            ->assertOk();

        $this->assertSame('picked_up', $shipment->fresh()->status);
    }

    public function test_an_unsigned_webhook_is_rejected(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-nosig']);

        $this->postJson('/api/carriers/' . $company->id . '/webhook', [
            'data' => ['_id' => 'bosta-nosig', 'state' => ['value' => 'Delivered']],
        ])->assertStatus(401);

        $this->assertSame('pending', $shipment->fresh()->status, 'an unsigned call must change nothing');
    }

    public function test_a_wrongly_signed_webhook_is_rejected(): void
    {
        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-badsig']);

        $this->withHeaders(['X-Bosta-Signature' => 'not-the-secret'])
            ->postJson('/api/carriers/' . $company->id . '/webhook', [
                'data' => ['_id' => 'bosta-badsig', 'state' => ['value' => 'Delivered']],
            ])->assertStatus(401);

        $this->assertSame('pending', $shipment->fresh()->status);
    }

    public function test_a_webhook_for_an_unknown_shipment_is_acknowledged_not_errored(): void
    {
        $company = $this->carrier();

        $this->withHeaders(['X-Bosta-Signature' => 'test-webhook-secret'])
            ->postJson('/api/carriers/' . $company->id . '/webhook', [
                'data' => ['_id' => 'never-seen', 'trackingNumber' => '000', 'state' => ['value' => 'Delivered']],
            ])->assertStatus(202);
    }

    public function test_a_manual_carrier_does_not_accept_webhooks(): void
    {
        $company = $this->carrier(['integration_driver' => 'manual']);

        $this->withHeaders(['X-Bosta-Signature' => 'test-webhook-secret'])
            ->postJson('/api/carriers/' . $company->id . '/webhook', ['data' => ['_id' => 'x']])
            ->assertStatus(404);
    }

    public function test_the_webhook_works_while_the_storefront_is_closed(): void
    {
        $settings = \App\Models\StoreSetting::current();
        $settings->update(['is_store_active' => false]);
        \App\Models\StoreSetting::flushCurrent();

        $company = $this->carrier();
        $shipment = $this->shipmentFor($company);
        $shipment->update(['carrier_shipment_id' => 'bosta-closed']);

        $this->withHeaders(['X-Bosta-Signature' => 'test-webhook-secret'])
            ->postJson('/api/carriers/' . $company->id . '/webhook', [
                'data' => ['_id' => 'bosta-closed', 'trackingNumber' => '999', 'state' => ['value' => 'Delivered']],
            ])->assertOk();

        $settings->update(['is_store_active' => true]);
        \App\Models\StoreSetting::flushCurrent();
    }

    public function test_api_credentials_are_encrypted_at_rest(): void
    {
        $company = $this->carrier();

        $raw = \Illuminate\Support\Facades\DB::table('shipping_companies')
            ->where('id', $company->id)
            ->first();

        $this->assertNotSame('test-api-key', $raw->api_key, 'the key must not be stored in clear text');
        $this->assertSame('test-api-key', $company->fresh()->api_key, 'but must decrypt for use');
    }
    public function test_auto_create_registers_the_shipment_as_soon_as_it_is_made(): void
    {
        Http::fake(['*' => Http::response([
            'data' => ['_id' => 'auto-1', 'trackingNumber' => 'AUTO123', 'state' => ['value' => 'Created']],
        ], 201)]);

        $company = $this->carrier(['auto_create_shipment' => true]);
        $shipment = $this->shipmentFor($company);

        $shipment->refresh();
        $this->assertSame('auto-1', $shipment->carrier_shipment_id, 'registered without anyone pressing a button');
        $this->assertSame('AUTO123', $shipment->tracking_number);
        Http::assertSentCount(1);
    }

    public function test_auto_create_stays_off_when_the_carrier_does_not_want_it(): void
    {
        Http::fake();

        $company = $this->carrier(['auto_create_shipment' => false]);
        $this->shipmentFor($company);

        Http::assertNothingSent();
    }

    public function test_a_manual_carrier_never_calls_out(): void
    {
        Http::fake();

        $company = $this->carrier(['integration_driver' => 'manual', 'auto_create_shipment' => true]);
        $this->shipmentFor($company);

        Http::assertNothingSent();
    }
}
