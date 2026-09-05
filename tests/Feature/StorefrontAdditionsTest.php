<?php

namespace Tests\Feature;

use App\Livewire\Site\AddToCartButton;
use App\Livewire\Site\CheckoutPage;
use App\Models\Product;
use App\Models\ShippingCity;
use App\Models\StoreSetting;
use App\Services\PaymentSettingsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * InstaPay, the welcome coupon popup and the floating WhatsApp button.
 */
class StorefrontAdditionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        StoreSetting::flushCurrent();
    }

    private function settings(array $values): void
    {
        StoreSetting::current()->update($values);
        StoreSetting::flushCurrent();
    }

    private function fillCart(): void
    {
        $product = Product::query()
            ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('stock_quantity', '>', 0))
            ->firstOrFail();

        DB::table('cart_items')->delete();
        DB::table('carts')->delete();

        Livewire::test(AddToCartButton::class, [
            'productId' => $product->id,
            'variantId' => $product->variants->where('is_active', true)->first()->id,
            'quantity' => 1,
        ])->call('addToCart');
    }

    // -------------------------------------------------------------- InstaPay

    public function test_instapay_is_offered_only_when_switched_on(): void
    {
        $this->settings(['instapay_enabled' => false]);
        $this->assertArrayNotHasKey('instapay', app(PaymentSettingsService::class)->availablePaymentMethods());

        $this->settings(['instapay_enabled' => true]);
        $this->assertArrayHasKey('instapay', app(PaymentSettingsService::class)->availablePaymentMethods());
    }

    /**
     * The money moves before the order exists, so the shop has no other proof
     * it arrived. The global "require proof" switch must not turn this off.
     */
    public function test_an_instapay_receipt_is_always_required(): void
    {
        $this->settings(['instapay_enabled' => true, 'payment_proof_required' => false]);

        $payments = app(PaymentSettingsService::class);

        $this->assertTrue($payments->requiresPaymentProof('instapay'));
        $this->assertFalse($payments->requiresPaymentProof('bank_transfer'));
    }

    public function test_the_checkout_shows_the_instapay_link_and_account(): void
    {
        $this->settings([
            'instapay_enabled' => true,
            'instapay_handle' => 'osama3lwany@instapay',
            'instapay_link' => 'https://ipn.eg/S/osama3lwany/instapay/5s3uRq',
        ]);

        $this->fillCart();

        $html = Livewire::test(CheckoutPage::class)->set('payment_method', 'instapay')->html();

        $this->assertStringContainsString('https://ipn.eg/S/osama3lwany/instapay/5s3uRq', $html);
        $this->assertStringContainsString('osama3lwany@instapay', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_an_instapay_order_without_a_receipt_is_refused(): void
    {
        $this->settings([
            'instapay_enabled' => true,
            'payment_proof_required' => false,
            'instapay_link' => 'https://ipn.eg/S/test/instapay/AAA',
        ]);

        $this->fillCart();

        $city = ShippingCity::query()->where('is_active', true)->firstOrFail();

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'عميلة')
            ->set('customer_email', 'buyer@example.com')
            ->set('customer_phone', '01099887766')
            ->set('address', 'عنوان')
            ->set('shipping_city_id', $city->id)
            ->set('city', $city->name_ar)
            ->set('payment_method', 'instapay')
            ->call('placeOrder')
            ->assertHasErrors('payment_proof');
    }

    /**
     * Regression: the storefront view composer used to overwrite whatever the
     * view was given, and its "paymentMethods" - the footer's payment icons -
     * silently replaced the checkout's list of methods a shopper can pick.
     */
    public function test_the_checkout_shows_every_enabled_method_not_the_footer_icons(): void
    {
        $this->settings([
            'cash_on_delivery_enabled' => true,
            'instapay_enabled' => true,
            'instapay_link' => 'https://ipn.eg/S/test/instapay/AAA',
        ]);

        $this->fillCart();

        $html = Livewire::test(CheckoutPage::class)->html();

        preg_match_all('/<input type="radio" value="([^"]+)"/', $html, $matches);

        $this->assertContains('instapay', $matches[1], 'the checkout dropped an enabled payment method');
        $this->assertContains('cash_on_delivery', $matches[1]);
    }

    // ---------------------------------------------------------- coupon popup

    public function test_the_popup_only_appears_when_switched_on_and_filled_in(): void
    {
        $this->settings(['coupon_popup_enabled' => false]);
        $this->assertStringNotContainsString('coupon-popup-overlay', $this->get('/')->getContent());

        // Switched on but with nothing to say: still nothing.
        $this->settings([
            'coupon_popup_enabled' => true,
            'coupon_popup_title_ar' => null,
            'coupon_popup_title_en' => null,
            'coupon_popup_code' => null,
        ]);
        $this->assertStringNotContainsString('coupon-popup-overlay', $this->get('/')->getContent());
    }

    public function test_the_popup_uses_the_wording_and_timing_from_the_dashboard(): void
    {
        $this->settings([
            'coupon_popup_enabled' => true,
            'coupon_popup_title_ar' => 'خصم ١٠٪ على أول طلب',
            'coupon_popup_code' => 'ELLE10',
            'coupon_popup_button_label_ar' => 'تسوقي الآن',
            'coupon_popup_delay' => 6,
            'coupon_popup_remember_days' => 3,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('خصم ١٠٪ على أول طلب', $html);
        $this->assertStringContainsString('ELLE10', $html);
        $this->assertStringContainsString('تسوقي الآن', $html);
        $this->assertStringContainsString('6000', $html, 'the delay is not the configured one');
        $this->assertStringContainsString('3 * 86400000', $html, 'the dismissal window is wrong');
    }

    public function test_the_popup_image_backs_the_whole_card(): void
    {
        $this->settings([
            'coupon_popup_enabled' => true,
            'coupon_popup_title_ar' => 'عرض',
            'coupon_popup_code' => 'ELLE10',
            'coupon_popup_image' => 'settings/popup/promo.png',
            'coupon_popup_overlay' => 65,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('has-image', $html);
        $this->assertStringContainsString('--popup-image: url(', $html);
        $this->assertStringContainsString('settings/popup/promo.png', $html);
        $this->assertStringContainsString('--popup-veil: 0.65', $html);

        // The old layout put the picture in a strip of its own above the text.
        $this->assertStringNotContainsString('coupon-popup-image', $html);
    }

    public function test_a_popup_without_an_image_asks_for_no_background(): void
    {
        $this->settings([
            'coupon_popup_enabled' => true,
            'coupon_popup_title_ar' => 'عرض',
            'coupon_popup_image' => null,
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('coupon-popup-overlay', $html);
        $this->assertStringNotContainsString('--popup-image', $html);
    }

    // ------------------------------------------------------- WhatsApp button

    public function test_the_whatsapp_button_turns_a_local_number_into_a_wa_me_link(): void
    {
        $this->settings([
            'whatsapp_button_enabled' => true,
            'whatsapp' => '01022434418',
            'whatsapp_button_message_ar' => 'السلام عليكم',
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('wa.me/201022434418', $html);
        $this->assertStringContainsString(rawurlencode('السلام عليكم'), $html);
        $this->assertStringContainsString('whatsapp-fab', $html);
    }

    public function test_the_whatsapp_button_stays_away_when_it_has_nothing_to_link_to(): void
    {
        $this->settings(['whatsapp_button_enabled' => true, 'whatsapp' => null]);
        $this->assertStringNotContainsString('whatsapp-fab', $this->get('/')->getContent());

        $this->settings(['whatsapp_button_enabled' => false, 'whatsapp' => '01022434418']);
        $this->assertStringNotContainsString('whatsapp-fab', $this->get('/')->getContent());
    }
}
