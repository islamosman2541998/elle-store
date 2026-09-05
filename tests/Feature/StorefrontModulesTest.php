<?php

namespace Tests\Feature;

use App\Livewire\Site\CustomerLogin;
use App\Livewire\Site\CustomerRegister;
use App\Livewire\Site\WishlistButton;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Customer accounts, categories, wishlist and the small content modules.
 *
 * Uses DatabaseTransactions so it runs against the real schema and data
 * without leaving anything behind.
 */
class StorefrontModulesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_customer_can_register_and_sign_in(): void
    {
        // Hit the page first so the container request carries a session, the
        // way the web middleware provides one in the browser.
        $this->get('/customer/register')->assertOk();

        Livewire::test(CustomerRegister::class)
            ->set('name', 'عميلة جديدة')
            ->set('email', 'newcustomer@example.com')
            ->set('phone', '01099999999')
            ->set('password', 'Passw0rd!23')
            ->set('password_confirmation', 'Passw0rd!23')
            ->call('register')
            ->assertHasNoErrors();

        $customer = Customer::query()->where('email', 'newcustomer@example.com')->first();

        $this->assertNotNull($customer, 'customer row was created');
        $this->assertNotSame('Passw0rd!23', $customer->password, 'password is not stored in clear text');

        auth('customer')->logout();

        Livewire::test(CustomerLogin::class)
            ->set('email', 'newcustomer@example.com')
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertFalse(auth('customer')->check(), 'a wrong password must not sign the customer in');

        Livewire::test(CustomerLogin::class)
            ->set('email', 'newcustomer@example.com')
            ->set('password', 'Passw0rd!23')
            ->call('login');

        $this->assertTrue(auth('customer')->check(), 'the correct password signs the customer in');
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        $this->get('/customer/register')->assertOk();

        Customer::query()->create([
            'name' => 'موجودة',
            'email' => 'taken@example.com',
            'phone' => '01088888888',
            'password' => bcrypt('Passw0rd!23'),
        ]);

        Livewire::test(CustomerRegister::class)
            ->set('name', 'عميلة')
            ->set('email', 'taken@example.com')
            ->set('phone', '01077777777')
            ->set('password', 'Passw0rd!23')
            ->set('password_confirmation', 'Passw0rd!23')
            ->call('register')
            ->assertHasErrors('email');
    }

    public function test_a_new_category_appears_and_can_be_hidden(): void
    {
        $category = Category::query()->create([
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        foreach ([['ar', 'قسم اختبار'], ['en', 'Test Category']] as [$locale, $name]) {
            CategoryTranslation::query()->create([
                'category_id' => $category->id,
                'locale' => $locale,
                'name' => $name,
                'slug' => 'test-category',
            ]);
        }

        $this->get('/')->assertOk()->assertSee('قسم اختبار', false);

        $category->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('قسم اختبار', false);
    }

    public function test_the_wishlist_button_adds_and_removes(): void
    {
        $product = Product::query()->where('is_active', true)->firstOrFail();

        $component = Livewire::test(WishlistButton::class, ['productId' => $product->id]);

        $component->call('toggleWishlist');
        $this->assertDatabaseHas('wishlist_items', ['product_id' => $product->id]);

        $component->call('toggleWishlist');
        $this->assertDatabaseMissing('wishlist_items', ['product_id' => $product->id]);
    }

    public function test_content_modules_store_their_records(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'عميلة',
            'email' => 'c@example.com',
            'phone' => '01000000000',
            'subject' => 'استفسار',
            'message' => 'عندي سؤال',
            'is_read' => false,
        ]);

        $this->assertTrue($message->exists);
        $this->assertFalse((bool) $message->is_read, 'a new message starts unread');

        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'sub@example.com',
            'is_active' => true,
        ]);

        $this->assertTrue($subscriber->exists);
    }

    public function test_every_content_page_renders(): void
    {
        foreach (['about', 'contact', 'shipping-policy', 'return-policy', 'privacy-policy', 'terms'] as $slug) {
            $response = $this->get('/pages/' . $slug);

            $response->assertOk();
            $this->assertGreaterThan(
                5000,
                strlen($response->getContent()),
                "page /{$slug} should render with the full site chrome"
            );
        }
    }

    public function test_core_storefront_routes_respond(): void
    {
        foreach (['/', '/shop', '/cart', '/wishlist', '/checkout', '/track-order'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
