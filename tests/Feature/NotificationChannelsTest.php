<?php

namespace Tests\Feature;

use App\Mail\StoreNotificationMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingCity;
use App\Models\ShippingCompany;
use App\Models\StoreSetting;
use App\Services\Notifications\MailConfigurator;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\WhatsappGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Email and WhatsApp notifications.
 *
 * Both channels are exercised with fakes, so the assertions are about what the
 * store decides to send and what stops it - never about a live provider.
 */
class NotificationChannelsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The settings row is memoised in a static for the life of the
        // request; in tests that static outlives the rolled-back data, so
        // clear it (and the mailer it configured) between tests.
        StoreSetting::flushCurrent();
        app(MailConfigurator::class)->reset();
    }

    private function settings(array $overrides = []): StoreSetting
    {
        $settings = StoreSetting::current();

        $settings->update(array_merge([
            'email_notifications_enabled' => true,
            'admin_notification_email' => 'owner@example.com',
            'notify_admin_new_order' => true,
            'notify_admin_new_payment' => true,
            'notify_customer_new_order' => true,
            'notify_customer_order_status' => true,
            'notify_customer_invoice_created' => true,
            'notify_customer_shipment' => true,

            'whatsapp' => '01022434418',
            'whatsapp_notifications_enabled' => true,
            'whatsapp_api_provider' => 'cloud_api',
            'whatsapp_api_token' => 'wa-token',
            'whatsapp_sender_id' => '1234567890',
            'whatsapp_notify_admin_new_order' => true,
            'whatsapp_notify_admin_new_payment' => true,
            'whatsapp_notify_customer_new_order' => true,
            'whatsapp_notify_customer_order_status' => true,
            'whatsapp_notify_customer_invoice' => true,
            'whatsapp_notify_customer_shipment' => true,
        ], $overrides));

        StoreSetting::flushCurrent();
        app(MailConfigurator::class)->reset();

        return StoreSetting::current();
    }

    private function order(): Order
    {
        $order = Order::query()->where('status', '!=', 'cancelled')->latest('id')->firstOrFail();

        $order->update([
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '01099887766',
        ]);

        return $order->fresh();
    }

    private function fakeWhatsappOk(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200)]);
    }

    // ------------------------------------------------------- both channels

    public function test_a_new_order_notifies_the_owner_on_both_channels(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings();

        app(NotificationDispatcher::class)->adminNewOrder($this->order());

        Mail::assertSent(StoreNotificationMail::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/messages'));
    }

    public function test_a_new_order_notifies_the_customer_on_both_channels(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings();

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertSent(StoreNotificationMail::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data()), '201099887766'));
    }

    public function test_every_event_reaches_whatsapp(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings();

        $order = $this->order();
        $dispatcher = app(NotificationDispatcher::class);

        $dispatcher->adminNewOrder($order);
        $dispatcher->customerNewOrder($order);
        $dispatcher->adminNewPayment($order);
        $dispatcher->customerOrderStatus($order);

        $invoice = Invoice::query()->where('order_id', $order->id)->first()
            ?? Invoice::query()->latest('id')->first();

        if ($invoice) {
            $dispatcher->customerInvoiceCreated($invoice);
        }

        $dispatcher->customerShipment($this->shipment($order));

        Http::assertSentCount($invoice ? 6 : 5);
    }

    private function shipment(Order $order): Shipment
    {
        $company = ShippingCompany::query()->firstOrCreate(
            ['code' => 'notif-test'],
            ['name' => 'Test Carrier', 'is_active' => true, 'sort_order' => 90]
        );

        return Shipment::query()->create([
            'shipment_number' => 'SHP-NOTIF-' . uniqid(),
            'order_id' => $order->id,
            'shipping_company_id' => $company->id,
            'shipping_city_id' => ShippingCity::query()->value('id'),
            'status' => 'assigned',
            'tracking_number' => 'TRK-999',
            'tracking_url' => 'https://carrier.test/track/TRK-999',
        ]);
    }

    // --------------------------------------------- the channels are separate

    public function test_whatsapp_still_sends_when_email_is_switched_off(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings(['email_notifications_enabled' => false]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertNothingSent();
        Http::assertSentCount(1);
    }

    public function test_email_still_sends_when_whatsapp_is_switched_off(): void
    {
        Mail::fake();
        Http::fake();
        $this->settings(['whatsapp_notifications_enabled' => false]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertSent(StoreNotificationMail::class);
        Http::assertNothingSent();
    }

    public function test_a_failing_whatsapp_provider_does_not_stop_the_email(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['error' => ['message' => 'Invalid token']], 401)]);
        $this->settings();

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertSent(StoreNotificationMail::class);
    }

    public function test_a_failing_mailer_does_not_stop_whatsapp(): void
    {
        $this->fakeWhatsappOk();
        $this->settings();

        // No Mail::fake here, and a host that cannot resolve: the mail send
        // throws, and WhatsApp must still go out.
        $this->settings(['mail_mailer' => 'smtp', 'mail_host' => 'invalid.invalid', 'mail_port' => 2525]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Http::assertSentCount(1);
    }

    // ------------------------------------------------------- per-event gates

    public function test_a_single_whatsapp_event_can_be_switched_off(): void
    {
        Mail::fake();
        Http::fake();
        $this->settings(['whatsapp_notify_customer_new_order' => false]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Http::assertNothingSent();
        // Email is unaffected by the WhatsApp switch.
        Mail::assertSent(StoreNotificationMail::class);
    }

    public function test_a_single_email_event_can_be_switched_off(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings(['notify_admin_new_order' => false]);

        app(NotificationDispatcher::class)->adminNewOrder($this->order());

        Mail::assertNothingSent();
        Http::assertSentCount(1);
    }

    // ----------------------------------------------------- gateway behaviour

    public function test_whatsapp_is_not_configured_until_its_fields_are_filled(): void
    {
        $this->settings(['whatsapp_api_token' => null]);

        $gateway = app(WhatsappGateway::class);

        $this->assertFalse($gateway->isConfigured());
        $this->assertContains('whatsapp_api_token', $gateway->missingFields());
    }

    public function test_nothing_is_sent_while_whatsapp_is_unconfigured(): void
    {
        Mail::fake();
        Http::fake();
        $this->settings(['whatsapp_sender_id' => null]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Http::assertNothingSent();
    }

    /** @dataProvider phoneNumbers */
    public function test_local_numbers_are_converted_to_international(string $input, string $expected): void
    {
        $this->assertSame($expected, app(WhatsappGateway::class)->normalisePhone($input));
    }

    public static function phoneNumbers(): array
    {
        return [
            'local egyptian' => ['01022434418', '+201022434418'],
            'with spaces' => ['010 2243 4418', '+201022434418'],
            'already international' => ['+201022434418', '+201022434418'],
            'double zero prefix' => ['00201022434418', '+201022434418'],
            'country code, no plus' => ['201022434418', '+201022434418'],
        ];
    }

    public function test_each_provider_posts_to_its_own_endpoint(): void
    {
        $expectations = [
            'cloud_api' => 'graph.facebook.com',
            'ultramsg' => 'api.ultramsg.com',
            'twilio' => 'api.twilio.com',
        ];

        foreach ($expectations as $provider => $host) {
            Http::fake(['*' => Http::response(['ok' => true], 200)]);

            $this->settings([
                'whatsapp_api_provider' => $provider,
                'whatsapp_api_url' => null,
                'whatsapp_api_token' => 'token',
                'whatsapp_sender_id' => 'sender',
                'whatsapp_api_secret' => 'secret',
                'whatsapp_from_number' => '+14155238886',
            ]);

            app(WhatsappGateway::class)->send('01099887766', 'hi', 'test');

            Http::assertSent(
                fn ($request) => str_contains($request->url(), $host),
                "provider {$provider} should post to {$host}"
            );
        }
    }

    // ------------------------------------------------------------ mail setup

    public function test_mail_settings_from_the_dashboard_drive_the_mailer(): void
    {
        $this->settings([
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.dashboard.test',
            'mail_port' => 2525,
            'mail_username' => 'user@dashboard.test',
            'mail_password' => 'secret',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'shop@dashboard.test',
            'mail_from_name' => 'ELLE Shop',
        ]);

        app(MailConfigurator::class)->apply();

        $this->assertSame('smtp.dashboard.test', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('tls', config('mail.mailers.smtp.encryption'));
        $this->assertSame('shop@dashboard.test', config('mail.from.address'));
        $this->assertSame('ELLE Shop', config('mail.from.name'));
        $this->assertTrue(app(MailConfigurator::class)->isConfigured());
    }

    public function test_encryption_none_is_stored_as_no_encryption(): void
    {
        $this->settings([
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.dashboard.test',
            'mail_encryption' => 'none',
            'mail_from_address' => 'shop@dashboard.test',
        ]);

        app(MailConfigurator::class)->apply();

        $this->assertNull(config('mail.mailers.smtp.encryption'));
    }

    public function test_mail_secrets_are_encrypted_at_rest(): void
    {
        $this->settings(['mail_password' => 'plain-secret']);

        $raw = \Illuminate\Support\Facades\DB::table('store_settings')->where('id', 1)->first();

        $this->assertNotSame('plain-secret', $raw->mail_password);
        $this->assertSame('plain-secret', StoreSetting::current()->mail_password);
    }
}
