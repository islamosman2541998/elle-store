<?php

namespace Tests\Feature;

use App\Models\ProductTranslation;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * What search engines and advertising platforms actually receive.
 *
 * These render real storefront pages and read the markup, because every one
 * of these tags is invisible to a person browsing the site - the only way to
 * know they are there is to look.
 */
class SeoAndPixelsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        StoreSetting::flushCurrent();
    }

    private function productUrl(): string
    {
        return '/product/' . ProductTranslation::query()->value('slug');
    }

    private function settings(array $values): void
    {
        StoreSetting::current()->update($values);
        StoreSetting::flushCurrent();
    }

    // ------------------------------------------------------------------ SEO

    public function test_a_product_page_describes_itself_rather_than_the_store(): void
    {
        $html = $this->get($this->productUrl())->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/s', $html, $title);
        preg_match('/<link rel="canonical" href="([^"]+)"/', $html, $canonical);

        // Every product page used to share one generic title.
        $this->assertStringNotContainsString('تفاصيل المنتج', $title[1] ?? '');
        $this->assertStringContainsString('/product/', $canonical[1] ?? '');
    }

    public function test_a_shared_link_carries_a_preview(): void
    {
        $html = $this->get($this->productUrl())->getContent();

        foreach (['og:title', 'og:description', 'og:image', 'og:url', 'og:type'] as $tag) {
            $this->assertStringContainsString('property="' . $tag . '"', $html, "missing {$tag}");
        }

        $this->assertStringContainsString('name="twitter:card"', $html);
    }

    public function test_a_product_offers_price_and_availability_as_structured_data(): void
    {
        $html = $this->get($this->productUrl())->getContent();

        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $blocks);

        $schemas = array_map(fn ($json) => json_decode($json, true), $blocks[1]);

        foreach ($schemas as $index => $schema) {
            $this->assertIsArray($schema, "JSON-LD block {$index} is not valid JSON");
        }

        $product = collect($schemas)->firstWhere('@type', 'Product');

        $this->assertNotNull($product, 'no Product schema on the product page');
        $this->assertNotEmpty($product['offers']['price']);
        $this->assertStringContainsString('schema.org', $product['offers']['availability']);
    }

    public function test_the_sitemap_lists_the_catalogue(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'the sitemap is not valid XML');
        $this->assertGreaterThan(5, substr_count($xml, '/product/'));
    }

    public function test_robots_keeps_crawlers_out_of_private_pages(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /checkout', $robots);
        $this->assertStringContainsString('Sitemap: ', $robots);
    }

    public function test_a_store_set_to_noindex_is_closed_on_both_sides(): void
    {
        $this->settings(['robots' => 'noindex, nofollow']);

        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            $this->get('/')->getContent()
        );

        // robots.txt must agree with the meta tag, or crawlers still come in.
        $this->assertStringContainsString("Disallow: /\n", $this->get('/robots.txt')->getContent());
    }

    // --------------------------------------------------------------- pixels

    public function test_nothing_is_tracked_while_the_master_switch_is_off(): void
    {
        $this->settings([
            'tracking_enabled' => false,
            'meta_pixel_id' => '1234567890',
            'tiktok_pixel_id' => 'TTQ1',
        ]);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString('analytics.tiktok.com', $html);
    }

    /** @dataProvider platforms */
    public function test_each_platform_loads_from_its_own_dashboard_field(
        string $field,
        string $id,
        string $script
    ): void {
        $this->settings(['tracking_enabled' => true, $field => $id]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString($id, $html, "the id from {$field} never reached the page");
        $this->assertStringContainsString($script, $html, "the {$field} script never loaded");
    }

    public static function platforms(): array
    {
        return [
            'google tag manager' => ['google_tag_manager_id', 'GTM-TEST01', 'googletagmanager.com/gtm.js'],
            'ga4' => ['ga4_measurement_id', 'G-TEST0001', 'googletagmanager.com/gtag/js'],
            'meta pixel' => ['meta_pixel_id', '1234567890', 'connect.facebook.net'],
            'tiktok' => ['tiktok_pixel_id', 'TTQTEST01', 'analytics.tiktok.com'],
            'snapchat' => ['snapchat_pixel_id', 'snap-test-01', 'sc-static.net'],
            'linkedin' => ['linkedin_partner_id', 'LI12345', 'snap.licdn.com'],
            'x (twitter)' => ['twitter_pixel_id', 'tw-test-01', 'static.ads-twitter.com'],
            'pinterest' => ['pinterest_tag_id', 'PIN12345', 's.pinimg.com'],
        ];
    }

    public function test_only_the_configured_platform_loads(): void
    {
        $this->settings(['tracking_enabled' => true, 'meta_pixel_id' => '1234567890']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString('analytics.tiktok.com', $html);
        $this->assertStringNotContainsString('sc-static.net', $html);
    }

    public function test_a_product_view_is_reported_with_its_price(): void
    {
        $this->settings(['tracking_enabled' => true, 'meta_pixel_id' => '1234567890']);

        $html = $this->get($this->productUrl())->getContent();

        $this->assertStringContainsString('ViewContent', $html);

        // Blade's @js() unicode-escapes the payload, so decode before judging.
        preg_match("/elleTrack\('ViewContent',\s*JSON\.parse\('(.*?)'\)/s", $html, $call);

        $payload = json_decode(json_decode('"' . str_replace("'", '\\u0027', $call[1] ?? '') . '"', true), true);

        $this->assertIsArray($payload, 'the ViewContent payload could not be read');
        $this->assertGreaterThan(0, $payload['value']);
        $this->assertNotEmpty($payload['content_ids']);
    }
}
