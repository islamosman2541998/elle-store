<?php

namespace App\Livewire\Site;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Livewire\Component;
use App\Services\ProductPricingService;
use App\Services\StockService;
use App\Support\Media;

class ProductShow extends Component
{
    public string $slug;

    public int $productId;

    public ?int $selectedVariantId = null;

    public array $selectedAttributes = [];

    public int $quantity = 1;

    public ?string $selectedImage = null;

    public function mount(string $slug): void
    {
        $this->slug = $slug;

        $product = $this->findProductBySlug($slug);

        $this->productId = $product->id;

        // Already fully loaded with every relation - reuse it rather than
        // letting selectVariant()/render() fetch the same row again.
        $this->resolvedProduct = $product;

        $firstVariant = $product->variants
            ->where('is_active', true)
            ->first();

        if ($firstVariant) {
            $this->selectVariant($firstVariant->id, false);
        } else {
            $this->selectedImage = $product->main_image;
        }
    }

    public function updatedQuantity($value): void
    {
        $this->quantity = max(1, (int) $value);
    }

    public function increaseQuantity(): void
    {
        $this->quantity++;
    }

    public function decreaseQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function setMainImage(?string $image): void
    {
        if ($image) {
            $this->selectedImage = $image;
        }
    }

    public function selectAttributeValue($attributeId, $attributeValueId): void
    {
        $attributeId = (string) $attributeId;
        $attributeValueId = (string) $attributeValueId;

        $this->selectedAttributes[$attributeId] = $attributeValueId;

        $product = $this->product();

        $matchingVariant = $this->findVariantBySelectedAttributes($product);

        if ($matchingVariant) {
            $this->selectedVariantId = $matchingVariant->id;

            if ($matchingVariant->image) {
                $this->selectedImage = $matchingVariant->image;
            }

            return;
        }

        $this->selectedVariantId = null;

        $this->dispatch(
            'site-toast',
            type: 'warning',
            icon: '!',
            title: app()->getLocale() === 'ar' ? 'اختيار غير متاح' : 'Unavailable option',
            message: app()->getLocale() === 'ar'
                ? 'هذا الاختيار غير متوفر، جرّب اختيار قيمة أخرى'
                : 'This combination is not available, please choose another option'
        );
    }

    public function selectVariant(int $variantId, bool $updateImage = true): void
    {
        $product = $this->product();

        $variant = $product->variants
            ->where('is_active', true)
            ->firstWhere('id', $variantId);

        if (! $variant) {
            return;
        }

        $this->selectedVariantId = $variant->id;

        $this->selectedAttributes = $variant->attributeValues
            ->mapWithKeys(function ($item) {
                return [
                    (string) $item->attribute_id => (string) $item->attribute_value_id,
                ];
            })
            ->toArray();

        if ($updateImage && $variant->image) {
            $this->selectedImage = $variant->image;
        }

        if (! $this->selectedImage) {
            $this->selectedImage = $product->main_image;
        }
    }

    private function findProductBySlug(string $slug): Product
    {
        return Product::query()
            ->where('is_active', true)
            ->with($this->productRelations())
            ->where(function ($query) use ($slug) {
                $query->whereHas('translations', function ($translationQuery) use ($slug) {
                    $translationQuery->where('slug', $slug);
                });

                if (is_numeric($slug)) {
                    $query->orWhere('id', $slug);
                }
            })
            ->firstOrFail();
    }

    /**
     * Resolved product for this request. mount(), the variant/attribute
     * actions and render() all ask for it, and each call used to re-run the
     * query with all seventeen eager loads.
     */
    private ?Product $resolvedProduct = null;

    private function product(): Product
    {
        return $this->resolvedProduct ??= Product::query()
            ->where('is_active', true)
            ->with($this->productRelations())
            ->findOrFail($this->productId);
    }

    private function productRelations(): array
    {
        return [
            'transNow',
            'arabicTranslation',
            'englishTranslation',
            'category.transNow',
            'category.arabicTranslation',
            'category.englishTranslation',
            'brand.transNow',
            'brand.arabicTranslation',
            'brand.englishTranslation',
            'images',
            'discounts',
            'variants.transNow',
            'variants.arabicTranslation',
            'variants.englishTranslation',
            'variants.discounts',
            // modelName() reads ->transNow on attributes and their values, so
            // load it here instead of lazy-loading one query per option while
            // building the variant picker.
            'variants.attributeValues.attribute.transNow',
            'variants.attributeValues.attributeValue.transNow',
        ];
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

    private function findVariantBySelectedAttributes(Product $product): ?ProductVariant
    {
        $selectedAttributes = collect($this->selectedAttributes)
            ->filter()
            ->mapWithKeys(fn($value, $key) => [(string) $key => (string) $value]);

        if ($selectedAttributes->isEmpty()) {
            return null;
        }

        $variantGroups = $this->variantGroups($product);

        $requiredAttributeIds = $variantGroups
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values();

        foreach ($requiredAttributeIds as $attributeId) {
            if (! $selectedAttributes->has($attributeId)) {
                return null;
            }
        }

        return $product->variants
            ->where('is_active', true)
            ->first(function ($variant) use ($selectedAttributes, $requiredAttributeIds) {
                $variantAttributes = $variant->attributeValues
                    ->mapWithKeys(function ($item) {
                        return [
                            (string) $item->attribute_id => (string) $item->attribute_value_id,
                        ];
                    });

                foreach ($requiredAttributeIds as $attributeId) {
                    if (! isset($variantAttributes[$attributeId])) {
                        return false;
                    }

                    if ((string) $variantAttributes[$attributeId] !== (string) $selectedAttributes[$attributeId]) {
                        return false;
                    }
                }

                return true;
            });
    }

    private function variantGroups(Product $product): Collection
    {
        $groups = collect();

        $product->variants
            ->where('is_active', true)
            ->each(function ($variant) use ($groups) {
                $variant->attributeValues->each(function ($item) use ($groups) {
                    $attribute = $item->attribute;
                    $value = $item->attributeValue;

                    if (! $attribute || ! $value) {
                        return;
                    }

                    $attributeId = (string) $item->attribute_id;
                    $valueId = (string) $item->attribute_value_id;

                    if (! $groups->has($attributeId)) {
                        $groups->put($attributeId, [
                            'id' => $attributeId,
                            'name' => $this->modelName($attribute, app()->getLocale() === 'ar' ? 'اختيار' : 'Option'),
                            'values' => collect(),
                        ]);
                    }

                    $group = $groups->get($attributeId);

                    if (! $group['values']->has($valueId)) {
                        $group['values']->put($valueId, [
                            'id' => $valueId,
                            'name' => $this->modelName($value, app()->getLocale() === 'ar' ? 'اختيار' : 'Option'),
                        ]);
                    }

                    $groups->put($attributeId, $group);
                });
            });

        return $groups
            ->map(function ($group) {
                $group['values'] = $group['values']->values();

                return $group;
            })
            ->values();
    }

    private function modelName($model, string $fallback): string
    {
        if (! $model) {
            return $fallback;
        }

        $locale = app()->getLocale();

        $translationFields = [
            'name',
            'title',
            'label',
            'value',
        ];

        try {
            if (method_exists($model, 'transNow') && $model->transNow) {
                foreach ($translationFields as $field) {
                    if (! empty($model->transNow->{$field})) {
                        return (string) $model->transNow->{$field};
                    }
                }
            }

            if ($locale === 'ar' && method_exists($model, 'arabicTranslation') && $model->arabicTranslation) {
                foreach ($translationFields as $field) {
                    if (! empty($model->arabicTranslation->{$field})) {
                        return (string) $model->arabicTranslation->{$field};
                    }
                }
            }

            if ($locale === 'en' && method_exists($model, 'englishTranslation') && $model->englishTranslation) {
                foreach ($translationFields as $field) {
                    if (! empty($model->englishTranslation->{$field})) {
                        return (string) $model->englishTranslation->{$field};
                    }
                }
            }

            if (method_exists($model, 'translations')) {
                $translations = $model->translations;

                $currentTranslation = $translations->firstWhere('locale', $locale)
                    ?? $translations->firstWhere('locale', 'ar')
                    ?? $translations->firstWhere('locale', 'en');

                if ($currentTranslation) {
                    foreach ($translationFields as $field) {
                        if (! empty($currentTranslation->{$field})) {
                            return (string) $currentTranslation->{$field};
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            //
        }

        $localizedFields = $locale === 'ar'
            ? [
                'name_ar',
                'title_ar',
                'label_ar',
                'value_ar',
                'display_name_ar',
                'text_ar',
            ]
            : [
                'name_en',
                'title_en',
                'label_en',
                'value_en',
                'display_name_en',
                'text_en',
            ];

        foreach ($localizedFields as $field) {
            if (! empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        $fallbackFields = [
            'name',
            'title',
            'label',
            'value',
            'display_name',
            'text',
            'slug',
            'code',
        ];

        foreach ($fallbackFields as $field) {
            if (! empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return $fallback;
    }

    private function galleryImages(Product $product, ?ProductVariant $variant): Collection
    {
        $images = collect();

        if ($variant?->image) {
            $images->push($variant->image);
        }

        if ($product->main_image) {
            $images->push($product->main_image);
        }

        foreach ($product->images as $imageItem) {
            if ($imageItem->image) {
                $images->push($imageItem->image);
            }
        }

        foreach ($product->variants->where('is_active', true) as $variantItem) {
            if ($variantItem->image) {
                $images->push($variantItem->image);
            }
        }

        return $images
            ->filter()
            ->unique()
            ->values()
            ->map(function ($image) {
                return [
                    'path' => $image,
                    // Gallery strip renders small - use the 500px derivative.
                    'url' => Media::url($image, 500),
                ];
            });
    }

    private function priceData(Product $product, ?ProductVariant $variant): array
    {
        $pricing = $variant
            ? app(ProductPricingService::class)->getVariantPrice($variant)
            : app(ProductPricingService::class)->getProductPrice($product);

        $originalPrice = (float) $pricing['original_price'];
        $finalPrice = (float) $pricing['final_price'];

        $hasSale = $finalPrice < $originalPrice;

        return [
            'original_price' => round($originalPrice, 2),
            'final_price' => round($finalPrice, 2),
            'discount_amount' => (float) $pricing['discount_amount'],
            'discount_source' => $pricing['discount_source'],
            'flash_sale_item_id' => $pricing['flash_sale_item_id'],
            'product_discount_id' => $pricing['product_discount_id'],
            'has_sale' => $hasSale,
            'discount_percentage' => $hasSale && $originalPrice > 0
                ? round((($originalPrice - $finalPrice) / $originalPrice) * 100)
                : null,
        ];
    }



    private function stockData(Product $product, ?ProductVariant $variant): array
    {
        $hasActiveVariants = $product->variants
            ->where('is_active', true)
            ->count() > 0;

        if ($hasActiveVariants && ! $variant) {
            return [
                'quantity' => 0,
                'in_stock' => false,
                'allow_backorder' => false,
                'invalid_selection' => true,
                'label' => app()->getLocale() === 'ar' ? 'اختيار غير متاح' : 'Unavailable option',
            ];
        }

        return [
            'quantity' => app(StockService::class)->availableQuantity($product, $variant),
            'in_stock' => app(StockService::class)->isInStock($product, $variant),
            'allow_backorder' => (bool) $product->allow_backorder,
            'invalid_selection' => false,
            'label' => app(StockService::class)->stockLabel($product, $variant),
        ];
    }

    private function relatedProducts(Product $product)
    {
        return Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, function ($query) use ($product) {
                $query->where('category_id', $product->category_id);
            })
            ->with([
                'transNow',
                'arabicTranslation',
                'englishTranslation',
                'category.transNow',
                'category.arabicTranslation',
                'category.englishTranslation',
                'brand.transNow',
                'brand.arabicTranslation',
                'brand.englishTranslation',
            ])
            ->latest()
            ->take(8)
            ->get();
    }

    public function render()
    {
        $product = $this->product();

        $translation = $product->transNow
            ?? $product->arabicTranslation
            ?? $product->englishTranslation;

        $selectedVariant = $this->selectedVariant($product);

        $variantGroups = $this->variantGroups($product);

        $priceData = $this->priceData($product, $selectedVariant);

        $stockData = $this->stockData($product, $selectedVariant);

        $galleryImages = $this->galleryImages($product, $selectedVariant);

        if (! $this->selectedImage && $galleryImages->count()) {
            $this->selectedImage = $galleryImages->first()['path'];
        }

        $relatedProducts = $this->relatedProducts($product);

        // Title, description, share image and Product structured data.
        $arabic = app()->getLocale() === 'ar';

        app(\App\Services\SeoService::class)
            ->forProduct($product, $priceData)
            ->breadcrumbs(array_values(array_filter([
                ['name' => $arabic ? 'الرئيسية' : 'Home', 'url' => route('site.home')],
                ['name' => $arabic ? 'المتجر' : 'Shop', 'url' => route('site.shop')],
                ['name' => $translation?->name ?: ('#' . $product->id), 'url' => url()->current()],
            ])));

        return view('livewire.site.product-show', [
            'product' => $product,
            'translation' => $translation,
            'selectedVariant' => $selectedVariant,
            'variantGroups' => $variantGroups,
            'priceData' => $priceData,
            'stockData' => $stockData,
            'galleryImages' => $galleryImages,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}