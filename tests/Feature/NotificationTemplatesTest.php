<?php

namespace Tests\Feature;

use App\Filament\Resources\NotificationTemplateResource\Pages\EditNotificationTemplate;
use App\Filament\Resources\NotificationTemplateResource\Pages\ListNotificationTemplates;
use App\Mail\StoreNotificationMail;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingCity;
use App\Models\ShippingCompany;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\Notifications\MailConfigurator;
use App\Services\Notifications\MessageBuilder;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationTemplates;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The wording of every notification is editable from the dashboard.
 *
 * These tests are about what the shop types coming out the other end
 * unchanged - placeholders filled, Arabic intact, empty lines gone.
 */
class NotificationTemplatesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Both are memoised in statics that outlive a rolled-back test.
        StoreSetting::flushCurrent();
        NotificationTemplate::flushCache();
        app(MailConfigurator::class)->reset();

        NotificationTemplate::syncMissing();
    }

    private function settings(array $overrides = []): StoreSetting
    {
        $settings = StoreSetting::current();

        $settings->update(array_merge([
            'email_notifications_enabled' => true,
            'admin_notification_email' => 'owner@example.com',
            'notify_admin_new_order' => true,
            'notify_customer_new_order' => true,
            'notify_customer_order_status' => true,
            'notify_customer_shipment' => true,

            'whatsapp' => '01022434418',
            'whatsapp_notifications_enabled' => true,
            'whatsapp_api_provider' => 'cloud_api',
            'whatsapp_api_token' => 'wa-token',
            'whatsapp_sender_id' => '1234567890',
            'whatsapp_notify_admin_new_order' => true,
            'whatsapp_notify_customer_new_order' => true,
            'whatsapp_notify_customer_order_status' => true,
            'whatsapp_notify_customer_shipment' => true,
        ], $overrides));

        StoreSetting::flushCurrent();

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

    private function template(string $event, string $channel): NotificationTemplate
    {
        return NotificationTemplate::query()
            ->where('event', $event)
            ->where('channel', $channel)
            ->firstOrFail();
    }

    private function fakeWhatsappOk(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200)]);
    }

    /** The text of the single WhatsApp message that was posted. */
    private function sentWhatsappBody(): string
    {
        $recorded = Http::recorded();

        $this->assertNotEmpty($recorded, 'no WhatsApp request was sent');

        return json_encode($recorded[0][0]->data(), JSON_UNESCAPED_UNICODE);
    }

    // -------------------------------------------------------------- the set

    public function test_every_event_has_a_row_for_both_channels(): void
    {
        foreach (NotificationTemplates::eventKeys() as $event) {
            foreach (NotificationTemplates::CHANNELS as $channel) {
                $this->assertDatabaseHas('notification_templates', [
                    'event' => $event,
                    'channel' => $channel,
                ]);
            }
        }
    }

    public function test_syncing_twice_does_not_duplicate_rows(): void
    {
        $before = NotificationTemplate::query()->count();

        NotificationTemplate::syncMissing();

        $this->assertSame($before, NotificationTemplate::query()->count());
    }

    // ---------------------------------------------------- edited wording wins

    public function test_an_edited_email_template_is_what_arrives(): void
    {
        Mail::fake();
        Http::fake();
        $this->settings();

        $this->template('customer_new_order', 'email')->update([
            'subject_ar' => 'طلبك {order_number} وصلنا',
            'subject_en' => 'We got order {order_number}',
            'body_ar' => "أهلاً {customer_name}\nالإجمالي {total}",
            'body_en' => "Hi {customer_name}\nTotal {total}",
        ]);

        $order = $this->order();

        app(NotificationDispatcher::class)->customerNewOrder($order);

        Mail::assertSent(StoreNotificationMail::class, function (StoreNotificationMail $mail) use ($order) {
            return str_contains($mail->subjectLine, $order->order_number)
                && str_contains($mail->bodyText, $order->customer_name)
                && ! str_contains($mail->bodyText, '{customer_name}');
        });
    }

    public function test_an_edited_whatsapp_template_is_what_is_posted(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings();

        $this->template('customer_new_order', 'whatsapp')->update([
            'body_ar' => 'كلام جديد تمامًا لـ {order_number}',
            'body_en' => 'Completely new wording for {order_number}',
        ]);

        $order = $this->order();

        app(NotificationDispatcher::class)->customerNewOrder($order);

        $sent = $this->sentWhatsappBody();

        $this->assertStringContainsString($order->order_number, $sent);
        $this->assertStringNotContainsString('👋', $sent, 'the default wording was sent instead of the edit');
    }

    public function test_the_two_channels_can_say_different_things(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings();

        $this->template('customer_new_order', 'email')->update([
            'body_ar' => 'نص الإيميل', 'body_en' => 'Email wording',
        ]);
        $this->template('customer_new_order', 'whatsapp')->update([
            'body_ar' => 'نص الواتساب', 'body_en' => 'WhatsApp wording',
        ]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertSent(StoreNotificationMail::class, fn (StoreNotificationMail $mail) => str_contains($mail->bodyText, 'الإيميل')
            || str_contains($mail->bodyText, 'Email wording'));

        $sent = $this->sentWhatsappBody();
        $this->assertTrue(
            str_contains($sent, 'الواتساب') || str_contains($sent, 'WhatsApp wording')
        );
    }

    public function test_a_blank_body_falls_back_to_the_built_in_wording(): void
    {
        Mail::fake();
        Http::fake();
        $this->settings();

        $this->template('customer_new_order', 'email')->update([
            'body_ar' => null,
            'body_en' => null,
        ]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertSent(StoreNotificationMail::class, fn (StoreNotificationMail $mail) => filled($mail->bodyText));
    }

    // ------------------------------------------------------- the off switch

    public function test_switching_one_template_off_silences_only_that_message(): void
    {
        Mail::fake();
        $this->fakeWhatsappOk();
        $this->settings();

        $this->template('customer_new_order', 'email')->update(['is_active' => false]);

        app(NotificationDispatcher::class)->customerNewOrder($this->order());

        Mail::assertNothingSent();
        Http::assertSentCount(1);
    }

    // ----------------------------------------------------------- rendering

    public function test_placeholders_are_filled_with_the_real_order(): void
    {
        $this->settings();
        $order = $this->order();

        $tokens = app(MessageBuilder::class)->orderTokens($order);

        $this->assertSame($order->order_number, $tokens['order_number']);
        $this->assertSame($order->customer_name, $tokens['customer_name']);
        $this->assertStringContainsString($order->order_number, $tokens['order_url']);
        $this->assertNotEmpty($tokens['items']);
        $this->assertStringContainsString('.', $tokens['total']);
    }

    /**
     * Regression: splitting on \R also matched the byte 0x85, the tail of
     * Arabic letters such as م, and shredded every Arabic message.
     */
    public function test_arabic_text_comes_through_untouched(): void
    {
        $arabic = "مرحبًا بكِ في متجر إيل\nنتمنى لكِ تجربة ممتعة معنا 💛";

        $rendered = app(MessageBuilder::class)->replace($arabic, ['order_number' => 'X']);

        $this->assertSame($arabic, $rendered);
        $this->assertTrue(mb_check_encoding($rendered, 'UTF-8'));
    }

    public function test_a_line_whose_placeholders_are_all_empty_is_dropped(): void
    {
        $rendered = app(MessageBuilder::class)->replace(
            "رقم الطلب: {order_number}\nرقم التتبع: {tracking_number}\nشكرًا لكِ",
            ['order_number' => 'ORD-1', 'tracking_number' => '']
        );

        $this->assertStringContainsString('ORD-1', $rendered);
        $this->assertStringNotContainsString('رقم التتبع', $rendered);
        $this->assertStringContainsString('شكرًا لكِ', $rendered);
    }

    public function test_a_line_without_placeholders_is_always_kept(): void
    {
        $rendered = app(MessageBuilder::class)->replace(
            "تابعي طلبك من هنا:\n{order_url}",
            ['order_url' => 'https://example.test/o/1']
        );

        $this->assertStringContainsString('تابعي طلبك من هنا:', $rendered);
        $this->assertStringContainsString('https://example.test/o/1', $rendered);
    }

    public function test_an_unknown_placeholder_is_left_visible_rather_than_deleting_its_line(): void
    {
        $rendered = app(MessageBuilder::class)->replace('Hello {not_a_real_token}', []);

        $this->assertSame('Hello {not_a_real_token}', $rendered);
    }

    public function test_a_missing_carrier_does_not_leave_a_dangling_label(): void
    {
        $this->settings();
        $order = $this->order();

        $company = ShippingCompany::query()->firstOrCreate(
            ['code' => 'tpl-test'],
            ['name' => 'Test Carrier', 'is_active' => true, 'sort_order' => 91]
        );

        $shipment = Shipment::query()->create([
            'shipment_number' => 'SHP-TPL-' . uniqid(),
            'order_id' => $order->id,
            'shipping_company_id' => $company->id,
            'shipping_city_id' => ShippingCity::query()->value('id'),
            'status' => 'assigned',
            'tracking_number' => null,
            'tracking_url' => null,
        ]);

        $builder = app(MessageBuilder::class);
        $body = $builder->build('customer_shipment', 'whatsapp', $builder->shipmentTokens($shipment))['body'];

        $this->assertStringNotContainsString('رقم التتبع', $body);
        $this->assertStringContainsString($order->order_number, $body);
    }

    // --------------------------------------------------------------- reset

    public function test_resetting_restores_the_original_wording(): void
    {
        $template = $this->template('customer_new_order', 'whatsapp');
        $original = $template->body_ar;

        $template->update(['body_ar' => 'حاجة مختلفة']);
        $template->resetToDefault();

        $this->assertSame($original, $template->fresh()->body_ar);
    }

    // ----------------------------------------------------------- dashboard

    public function test_the_dashboard_screens_load_and_save(): void
    {
        Auth::login(User::query()->firstOrFail());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListNotificationTemplates::class)->assertSuccessful();

        $template = $this->template('customer_order_status', 'email');

        Livewire::test(EditNotificationTemplate::class, ['record' => $template->getRouteKey()])
            ->assertSuccessful()
            ->set('data.body_ar', 'نص محرَّر من الداشبورد لـ {order_number}')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            'نص محرَّر من الداشبورد لـ {order_number}',
            $template->fresh()->body_ar
        );
    }

    public function test_the_preview_renders_without_touching_the_saved_row(): void
    {
        Auth::login(User::query()->firstOrFail());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $template = $this->template('customer_new_order', 'email');
        $before = $template->body_ar;

        Livewire::test(EditNotificationTemplate::class, ['record' => $template->getRouteKey()])
            ->set('data.body_ar', 'معاينة لـ {order_number}')
            ->mountAction('preview')
            ->assertSuccessful();

        $this->assertSame($before, $template->fresh()->body_ar);
    }
}
