@php
    $name = $translation?->name ?? (app()->getLocale() === 'ar' ? 'منتج' : 'Product');

    $shortDescription = $translation?->short_description;
    $description = $translation?->description;

    $categoryName =
        $product->category?->transNow?->name ??
        ($product->category?->arabicTranslation?->name ?? $product->category?->englishTranslation?->name);

    $brandName =
        $product->brand?->transNow?->name ??
        ($product->brand?->arabicTranslation?->name ?? $product->brand?->englishTranslation?->name);

    $categoryUrl = $product->category_id
        ? route('site.shop', ['category' => $product->category_id])
        : route('site.shop');

    $brandUrl = $product->brand_id ? route('site.shop', ['brand' => $product->brand_id]) : route('site.shop');

    $currency = $storeSettings->currency_symbol ?? ($storeSettings->currency_code ?? 'EGP');

    $mainImageUrl = \App\Support\Media::url($selectedImage, 800);

    $sku = $selectedVariant?->sku ?: $product->sku;

    $variantName = $selectedVariant
        ? $selectedVariant->transNow?->name ??
            ($selectedVariant->arabicTranslation?->name ?? $selectedVariant->englishTranslation?->name)
        : null;

    $hasVariants = $product->variants->where('is_active', true)->count() > 0;
@endphp

<section class="product-show-page">
    <div class="site-container">
        <div class="product-show-breadcrumb">
            <a href="{{ route('site.home') }}">
                {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
            </a>

            <span>/</span>

            <a href="{{ route('site.shop') }}">
                {{ app()->getLocale() === 'ar' ? 'المتجر' : 'Shop' }}
            </a>

            <span>/</span>

            <span>{{ $name }}</span>
        </div>

        <div class="product-show-layout">
            <div class="product-show-gallery">
                <div class="product-show-main-image">
                    @if ($mainImageUrl)
                        <img src="{{ $mainImageUrl }}" alt="{{ $name }}" draggable="false">
                    @else
                        <div class="product-show-placeholder">
                            {{ mb_substr($name, 0, 1) }}
                        </div>
                    @endif

                    @if ($priceData['has_sale'])
                        <span class="product-show-sale-badge">
                            -{{ $priceData['discount_percentage'] }}%
                        </span>
                    @endif
                </div>

                @if ($galleryImages->count() > 1)
                    <div class="product-show-thumbs">
                        @foreach ($galleryImages as $image)
                            <button type="button" wire:key="product-image-{{ md5($image['path']) }}"
                                wire:click="setMainImage('{{ $image['path'] }}')"
                                class="product-show-thumb {{ $selectedImage === $image['path'] ? 'is-active' : '' }}">
                                <img src="{{ $image['url'] }}" alt="{{ $name }}" draggable="false">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="product-show-info">
                <div class="product-show-meta">
                    @if ($categoryName)
                        <a href="{{ $categoryUrl }}">
                            {{ $categoryName }}
                        </a>
                    @endif

                    @if ($brandName)
                        <a href="{{ $brandUrl }}">
                            {{ $brandName }}
                        </a>
                    @endif
                </div>

                <h1 class="product-show-title">
                    {{ $name }}
                </h1>

                @if ($variantName)
                    <p class="product-show-variant-name">
                        {{ $variantName }}
                    </p>
                @endif

                @if ($shortDescription)
                    <p class="product-show-short-description">
                        {{ $shortDescription }}
                    </p>
                @endif

                <div class="product-show-price-row">
                    <div>
                        <span class="product-show-price">
                            {{ number_format($priceData['final_price'], 2) }} {{ $currency }}
                        </span>

                        @if ($priceData['has_sale'])
                            <span class="product-show-old-price">
                                {{ number_format($priceData['original_price'], 2) }} {{ $currency }}
                            </span>
                        @endif
                    </div>

                    @if ($stockData['invalid_selection'] ?? false)
                        <span class="product-show-stock out-stock">
                            {{ app()->getLocale() === 'ar' ? 'اختيار غير متاح' : 'Unavailable option' }}
                        </span>
                    @elseif ($stockData['in_stock'])
                        <span class="product-show-stock in-stock">
                            {{ app()->getLocale() === 'ar' ? 'متوفر' : 'In stock' }}
                        </span>
                    @else
                        <span class="product-show-stock out-stock">
                            {{ app()->getLocale() === 'ar' ? 'نفد المخزون' : 'Out of stock' }}
                        </span>
                    @endif
                </div>

                @if ($hasVariants && $variantGroups->count())
                    <div class="product-show-variants">
                        @foreach ($variantGroups as $group)
                            <div class="product-show-variant-group" wire:key="variant-group-{{ $group['id'] }}">
                                <h4>
                                    {{ $group['name'] }}
                                </h4>

                                <div class="product-show-variant-values">
                                    @foreach ($group['values'] as $value)
                                        @php
                                            $isSelected = ($selectedAttributes[$group['id']] ?? null) == $value['id'];
                                        @endphp

                                        <button type="button"
                                            wire:key="variant-value-{{ $group['id'] }}-{{ $value['id'] }}"
                                            wire:click="selectAttributeValue('{{ $group['id'] }}', '{{ $value['id'] }}')"
                                            class="{{ $isSelected ? 'is-selected' : '' }}">
                                            {{ $value['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="product-show-purchase-box">
                    <div class="product-show-quantity">
                        <span>
                            {{ app()->getLocale() === 'ar' ? 'الكمية' : 'Quantity' }}
                        </span>

                        <div>
                            <button type="button" wire:click="decreaseQuantity">-</button>

                            <input type="number" min="1" wire:model.live.debounce.300ms="quantity">

                            <button type="button" wire:click="increaseQuantity">+</button>
                        </div>
                    </div>

                    <div class="product-show-actions">
                        <div class="product-show-add-action">
                            @livewire(
                                'site.add-to-cart-button',
                                [
                                    'productId' => $product->id,
                                    'variantId' => $selectedVariant?->id,
                                    'quantity' => $quantity,
                                ],
                                key('product-show-add-desktop-' . $product->id . '-' . ($selectedVariant?->id ?? 'base'))
                            )
                        </div>

                        <div class="product-show-wishlist-action">
                            @livewire(
                                'site.wishlist-button',
                                [
                                    'productId' => $product->id,
                                ],
                                key('product-show-wishlist-' . $product->id)
                            )
                        </div>
                    </div>
                </div>

                <div class="product-show-details-list">
                    @if ($sku)
                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'كود المنتج' : 'SKU' }}</span>
                            <strong>{{ $sku }}</strong>
                        </div>
                    @endif

                    @if ($brandName)
                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'البراند' : 'Brand' }}</span>
                            <strong>{{ $brandName }}</strong>
                        </div>
                    @endif

                    @if ($categoryName)
                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'القسم' : 'Category' }}</span>
                            <strong>{{ $categoryName }}</strong>
                        </div>
                    @endif

                    @if ($product->manage_stock)
                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'المخزون' : 'Stock' }}</span>
                            <strong>
                                {{ $stockData['quantity'] }}
                            </strong>
                        </div>
                    @endif
                </div>

                <div class="product-show-benefits">
                    <div>
                        <span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7h11v10H3V7Zm11 3h4l3 3v4h-7v-7Z" />
                            </svg>
                        </span>

                        <p>
                            {{ app()->getLocale() === 'ar' ? 'شحن سريع وآمن' : 'Fast & secure delivery' }}
                        </p>
                    </div>

                    <div>
                        <span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3 4 7v6c0 5 3.5 7.5 8 8 4.5-.5 8-3 8-8V7l-8-4Z" />
                            </svg>
                        </span>

                        <p>
                            {{ app()->getLocale() === 'ar' ? 'دفع آمن' : 'Secure payment' }}
                        </p>
                    </div>

                    <div>
                        <span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0 1 19 5M19 5v5h-5" />
                            </svg>
                        </span>

                        <p>
                            {{ app()->getLocale() === 'ar' ? 'سهولة الاستبدال' : 'Easy exchange' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if ($description)
            <div class="product-show-description-box">
                <h2>
                    {{ app()->getLocale() === 'ar' ? 'وصف المنتج' : 'Product Description' }}
                </h2>

                <div>
                    {!! $description !!}
                </div>
            </div>
        @endif

        @if ($relatedProducts->count())
            <div class="product-show-related">
                <div class="home-section-head">
                    <span class="home-section-badge">
                        {{ app()->getLocale() === 'ar' ? 'منتجات مشابهة' : 'Related Products' }}
                    </span>

                    <h2 class="home-section-title">
                        {{ app()->getLocale() === 'ar' ? 'قد يعجبك أيضًا' : 'You may also like' }}
                    </h2>
                </div>

                <div class="product-show-related-grid">
                    @foreach ($relatedProducts as $relatedProduct)
                        @include('site.partials.product-card', [
                            'product' => $relatedProduct,
                            'cardKey' => 'related-product-' . $relatedProduct->id,
                        ])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="product-show-mobile-bar">
        <div>
            <span>
                {{ number_format($priceData['final_price'], 2) }} {{ $currency }}
            </span>

            @if ($priceData['has_sale'])
                <small>
                    {{ number_format($priceData['original_price'], 2) }} {{ $currency }}
                </small>
            @endif
        </div>

        @livewire(
            'site.add-to-cart-button',
            [
                'productId' => $product->id,
                'variantId' => $selectedVariant?->id,
                'quantity' => $quantity,
            ],
            key('product-show-add-mobile-' . $product->id . '-' . ($selectedVariant?->id ?? 'base'))
        )
    </div>
</section>
