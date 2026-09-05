<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\StorefrontChromeService;
use Illuminate\Support\Str;

/**
 * The page's search-engine and social-sharing metadata.
 *
 * Registered as a singleton: a controller or Livewire component describes the
 * page once with for*(), and the head partial reads it back. Anything left
 * unset falls back to the store settings, so a page that says nothing still
 * carries a sensible title, description and share image.
 */
class SeoService
{
    private array $overrides = [];

    /** @var array<int, array{name: string, url: ?string}> */
    private array $breadcrumbs = [];

    /** Structured data blocks to print as JSON-LD. */
    private array $schemas = [];

    public function set(array $values): self
    {
        $this->overrides = array_merge($this->overrides, array_filter(
            $values,
            fn ($value) => $value !== null && $value !== ''
        ));

        return $this;
    }

    public function breadcrumbs(array $crumbs): self
    {
        $this->breadcrumbs = $crumbs;

        return $this;
    }

    public function schema(array $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    // ------------------------------------------------------------ page types

    public function forProduct(Product $product, ?array $pricing = null): self
    {
        $translation = $product->transNow
            ?? $product->arabicTranslation
            ?? $product->englishTranslation;

        $name = $translation?->name ?: ('#' . $product->id);
        $mainImage = $product->images->firstWhere('is_main', true)
            ?? $product->images->first();

        $image = $this->imageUrl($mainImage?->image);

        $this->set([
            'title' => $translation?->meta_title ?: $name,
            'description' => $translation?->meta_description
                ?: Str::limit(strip_tags((string) ($translation?->short_description ?: $translation?->description)), 155),
            'image' => $image,
            'type' => 'product',
        ]);

        $price = $pricing['final_price'] ?? $product->price;
        $inStock = $product->variants->where('is_active', true)->sum('stock_quantity') > 0;

        // Product structured data is what earns the price and availability
        // line under a search result.
        $this->schema(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $this->get('description'),
            'image' => $image ? [$image] : null,
            'sku' => $product->sku,
            'brand' => $product->brand ? [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => url()->current(),
                'priceCurrency' => StoreSetting::current()->currency_code ?: 'EGP',
                'price' => number_format((float) $price, 2, '.', ''),
                'availability' => $inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ]));

        return $this;
    }

    public function forCategory(Category $category): self
    {
        $translation = $category->transNow
            ?? $category->arabicTranslation
            ?? $category->englishTranslation;

        return $this->set([
            'title' => $translation?->meta_title ?: $translation?->name,
            'description' => $translation?->meta_description
                ?: Str::limit(strip_tags((string) $translation?->description), 155),
            'image' => $this->imageUrl($category->image),
            'type' => 'website',
        ]);
    }

    /** Pages, blog posts - anything with the usual translated meta columns. */
    public function forRecord(object $record, ?string $imagePath = null, string $type = 'article'): self
    {
        return $this->set([
            'title' => $record->meta_title ?: $record->title,
            'description' => $record->meta_description
                ?: Str::limit(strip_tags((string) ($record->short_description ?? $record->content ?? '')), 155),
            'image' => $this->imageUrl($imagePath),
            'type' => $type,
        ]);
    }

    // --------------------------------------------------------------- reading

    public function get(string $key): ?string
    {
        if (isset($this->overrides[$key])) {
            return (string) $this->overrides[$key];
        }

        $settings = StoreSetting::current();
        $storeName = $settings->store_name ?: config('app.name');

        return match ($key) {
            'title' => $settings->meta_title ?: $storeName,
            'description' => $settings->meta_description,
            'keywords' => $settings->meta_keywords,
            'image' => $this->imageUrl($settings->og_image ?: $settings->logo),
            'type' => 'website',
            'site_name' => $storeName,
            'canonical' => url()->current(),
            'robots' => $settings->robots ?: 'index, follow',
            default => null,
        };
    }

    /** The full title, with the store name appended once. */
    public function title(): string
    {
        $title = (string) $this->get('title');
        $storeName = (string) $this->get('site_name');

        if ($storeName === '' || str_contains($title, $storeName)) {
            return $title;
        }

        return $title . ' | ' . $storeName;
    }

    public function breadcrumbList(): ?array
    {
        if ($this->breadcrumbs === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(
                fn (int $index, array $crumb) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'] ?? null,
                ]),
                array_keys($this->breadcrumbs),
                $this->breadcrumbs
            )),
        ];
    }

    /** Everything to print inside a <script type="application/ld+json">. */
    public function structuredData(): array
    {
        $blocks = $this->schemas;

        if ($crumbs = $this->breadcrumbList()) {
            $blocks[] = $crumbs;
        }

        return array_merge([$this->organisationSchema()], $blocks);
    }

    /** Who the shop is - printed on every page. */
    private function organisationSchema(): array
    {
        $settings = StoreSetting::current();
        $logo = $this->imageUrl($settings->logo);

        // Reuse the memoised list the header and footer already loaded rather
        // than running a second query on every page.
        $socials = app(StorefrontChromeService::class)->socialLinks()->pluck('url');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => $settings->store_name ?: config('app.name'),
            'url' => url('/'),
            'logo' => $logo,
            'image' => $logo,
            'description' => $settings->meta_description,
            'telephone' => $settings->phone,
            'email' => $settings->email,
            'sameAs' => collect($socials)->filter()->values()->all() ?: null,
        ]);
    }

    private function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
