<?php

namespace App\Livewire\Site;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use App\Services\CartService;
use App\Services\ProductPricingService;
use App\Services\StockService;
use App\Services\WishlistStateService;
use App\Support\Media;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dialog that lets a customer pick colour and size, then add to cart or
 * wishlist, without leaving the listing page they are browsing.
 *
 * A single instance is rendered in the site layout; product cards open it by
 * dispatching `open-quick-view` with a product id.
 */
class ProductQuickView extends Component
{
    public bool $open = false;

    public ?int $productId = null;

    public ?int $selectedVariantId = null;

    /** @var array<string, string> attribute id => attribute value id */
    public array $selectedAttributes = [];

    public int $quantity = 1;

    public ?string $selectedImage = null;

    public bool $wished = false;

    private ?Product $resolvedProduct = null;

    #[On('open-quick-view')]
    public function openQuickView(int $productId): void
    {
        $this->reset(['selectedVariantId', 'selectedAttributes', 'selectedImage']);

        $this->productId = $productId;
        $this->quantity = 1;
        $this->resolvedProduct = null;

        $product = $this->product();

        if (! $product) {
            return;
        }

        $this->wished = app(WishlistStateService::class)->has($product->id);
        $this->selectedImage = $product->main_image;

        // Preselect when there is only one possible variant.
        $activeVariants = $product->variants->where('is_active', true)->values();

        if ($activeVariants->count() === 1) {
            $this->applyVariant($activeVariants->first());
        }

        $this->open = true;
    }

    public function closeQuickView(): void
    {
        $this->open = false;
        $this->productId = null;
        $this->resolvedProduct = null;
        $this->reset(['selectedVariantId', 'selectedAttributes', 'selectedImage']);
    }

    public function selectAttributeValue(string $attributeId, string $attributeValueId): void
    {
        $this->selectedAttributes[$attributeId] = $attributeValueId;

        $product = $this->product();

        if (! $product) {
            return;
        }

        $match = $this->matchVariant($product);

        if ($match) {
            $this->applyVariant($match);

            return;
        }

        $this->selectedVariantId = null;
    }

    public function setMainImage(?string $image): void
    {
        if ($image) {
            $this->selectedImage = $image;
        }
    }

    public function increaseQuantity(): void
    {
        $this->quantity++;
    }

    public function decreaseQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToCart(): void
    {
        $product = $this->product();

        if (! $product) {
            return;
        }

        $activeVariants = $product->variants->where('is_active', true)->values();
        $variant = $this->selectedVariant($product);

        if ($activeVariants->count() && ! $variant) {
            $this->toast(
                'warning',
                '!',
                ar: ['اختر المواصفات', 'من فضلك اختاري المقاس واللون أولًا'],
                en: ['Choose options', 'Please choose the size and colour first']
            );

            return;
        }

        $result = app(CartService::class)->add($product, $variant, $this->quantity);

        if (! $result['ok']) {
            $this->toast(
                'error',
                '!',
                ar: ['غير متوفر', 'الكمية المطلوبة غير متوفرة حاليًا'],
                en: ['Unavailable', 'The requested quantity is not currently available']
            );

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('cart-updated')->to(CartCounter::class);

        $this->toast(
            'success',
            '✓',
            ar: ['تمت الإضافة', 'تم إضافة المنتج إلى السلة'],
            en: ['Added', 'Product has been added to cart']
        );

        $this->closeQuickView();
    }

    public function toggleWishlist(): void
    {
        $product = $this->product();

        if (! $product) {
            return;
        }

        $customerId = app(WishlistStateService::class)->customerId();

        $existing = WishlistItem::query()
            ->when(
                $customerId,
                fn ($query) => $query->where('customer_id', $customerId),
                fn ($query) => $query->where('session_id', session()->getId())
            )
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->wished = false;

            $this->toast(
                'success',
                '✓',
                ar: ['تم الحذف', 'تم حذف المنتج من المفضلة'],
                en: ['Removed', 'Product has been removed from wishlist']
            );
        } else {
            WishlistItem::query()->create([
                'customer_id' => $customerId,
                'session_id' => $customerId ? null : session()->getId(),
                'product_id' => $product->id,
            ]);

            $this->wished = true;

            $this->toast(
                'success',
                '♥',
                ar: ['تمت الإضافة', 'تم إضافة المنتج إلى المفضلة'],
                en: ['Added', 'Product has been added to wishlist']
            );
        }

        $this->dispatch('wishlist-updated');
        $this->dispatch('wishlist-updated')->to(WishlistCounter::class);
    }

    private function product(): ?Product
    {
        if ($this->productId === null) {
            return null;
        }

        return $this->resolvedProduct ??= Product::query()
            ->where('is_active', true)
            ->with(array_merge(CartService::productRelations(), ['images', 'category.transNow']))
            ->find($this->productId);
    }

    private function applyVariant(ProductVariant $variant): void
    {
        $this->selectedVariantId = $variant->id;

        $this->selectedAttributes = $variant->attributeValues
            ->mapWithKeys(fn ($item) => [
                (string) $item->attribute_id => (string) $item->attribute_value_id,
            ])
            ->toArray();

        if ($variant->image) {
            $this->selectedImage = $variant->image;
        }
    }

    private function selectedVariant(Product $product): ?ProductVariant
    {
        if (! $this->selectedVariantId) {
            return null;
        }

        return $product->variants
            ->where('is_active', true)
            ->firstWhere('id', $this->selectedVariantId);
    }

    /** The variant matching every currently selected attribute value, if any. */
    private function matchVariant(Product $product): ?ProductVariant
    {
        $selected = collect($this->selectedAttributes)->filter();

        if ($selected->isEmpty()) {
            return null;
        }

        return $product->variants
            ->where('is_active', true)
            ->first(function (ProductVariant $variant) use ($selected) {
                $variantAttributes = $variant->attributeValues
                    ->mapWithKeys(fn ($item) => [
                        (string) $item->attribute_id => (string) $item->attribute_value_id,
                    ]);

                foreach ($selected as $attributeId => $valueId) {
                    if (($variantAttributes[(string) $attributeId] ?? null) !== (string) $valueId) {
                        return false;
                    }
                }

                return true;
            });
    }

    /** Colour / size groups built from the product's active variants. */
    private function variantGroups(Product $product): Collection
    {
        $groups = collect();

        foreach ($product->variants->where('is_active', true) as $variant) {
            foreach ($variant->attributeValues as $item) {
                $attribute = $item->attribute;
                $value = $item->attributeValue;

                if (! $attribute || ! $value) {
                    continue;
                }

                $attributeId = (string) $item->attribute_id;
                $valueId = (string) $item->attribute_value_id;

                if (! $groups->has($attributeId)) {
                    $groups->put($attributeId, [
                        'id' => $attributeId,
                        'name' => $attribute->transNow?->name
                            ?? $attribute->arabicTranslation?->name
                            ?? $attribute->englishTranslation?->name
                            ?? (app()->getLocale() === 'ar' ? 'اختيار' : 'Option'),
                        'values' => collect(),
                    ]);
                }

                $group = $groups->get($attributeId);

                if (! $group['values']->has($valueId)) {
                    $group['values']->put($valueId, [
                        'id' => $valueId,
                        'name' => $value->transNow?->value
                            ?? $value->arabicTranslation?->value
                            ?? $value->englishTranslation?->value
                            ?? '-',
                        'color' => $value->color_code,
                    ]);

                    $groups->put($attributeId, $group);
                }
            }
        }

        return $groups->values();
    }

    private function galleryImages(Product $product): Collection
    {
        return collect([$this->selectedImage, $product->main_image])
            ->merge($product->images->pluck('image'))
            ->merge($product->variants->where('is_active', true)->pluck('image'))
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($path) => [
                'path' => $path,
                'url' => Media::url($path, 500),
            ]);
    }

    /**
     * @param  array{0: string, 1: string}  $ar
     * @param  array{0: string, 1: string}  $en
     */
    private function toast(string $type, string $icon, array $ar, array $en): void
    {
        [$title, $message] = app()->getLocale() === 'ar' ? $ar : $en;

        $this->dispatch('site-toast', type: $type, icon: $icon, title: $title, message: $message);
    }

    public function render()
    {
        $product = $this->open ? $this->product() : null;

        if (! $product) {
            return view('livewire.site.product-quick-view', [
                'product' => null,
                'translation' => null,
                'variantGroups' => collect(),
                'galleryImages' => collect(),
                'priceData' => null,
                'inStock' => false,
                'mainImageUrl' => null,
            ]);
        }

        $variant = $this->selectedVariant($product);

        $pricing = $variant
            ? app(ProductPricingService::class)->getVariantPrice($variant)
            : app(ProductPricingService::class)->getProductPrice($product);

        $originalPrice = (float) $pricing['original_price'];
        $finalPrice = (float) $pricing['final_price'];

        return view('livewire.site.product-quick-view', [
            'product' => $product,
            'translation' => $product->transNow
                ?? $product->arabicTranslation
                ?? $product->englishTranslation,
            'variantGroups' => $this->variantGroups($product),
            'galleryImages' => $this->galleryImages($product),
            'priceData' => [
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'has_sale' => $finalPrice < $originalPrice,
                'discount_percentage' => $finalPrice < $originalPrice && $originalPrice > 0
                    ? round((($originalPrice - $finalPrice) / $originalPrice) * 100)
                    : null,
            ],
            'inStock' => app(StockService::class)->isInStock($product, $variant),
            'mainImageUrl' => Media::url($this->selectedImage ?: $product->main_image, 800),
        ]);
    }
}
