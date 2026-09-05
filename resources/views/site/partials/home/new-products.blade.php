@php
    $newProducts = $homepage['new_products'] ?? [
        'enabled' => false,
        'title' => null,
        'items' => collect(),
    ];

    $products = $newProducts['items'] ?? collect();

    $newProductsUrl = route('site.shop', ['sort' => 'newest']);
@endphp

@if (($newProducts['enabled'] ?? false) && $products->count())
    <section class="home-new-products-section">
        <div class="site-container">
            <div class="home-section-head">
                <span class="home-section-badge">
                    {{ app()->getLocale() === 'ar' ? 'وصل حديثًا' : 'New Arrivals' }}
                </span>

                <h2 class="home-section-title">
                    {{ $newProducts['title'] }}
                </h2>

                <p class="home-section-description">
                    {{ app()->getLocale() === 'ar'
                        ? 'اكتشف أحدث المنتجات المضافة للمتجر بتصميمات عصرية ومميزة.'
                        : 'Discover the latest products added to the store with fresh modern styles.' }}
                </p>

                <a
                    href="{{ $newProductsUrl }}"
                    class="mt-4 inline-flex items-center justify-center rounded-full bg-dark px-6 py-3 text-xs font-black text-white transition hover:bg-brand"
                >
                    {{ app()->getLocale() === 'ar' ? 'عرض أحدث المنتجات' : 'View new arrivals' }}
                </a>
            </div>

            <div class="home-new-products-slider-wrap">
                @if ($products->count() > 1)
                    <button
                        type="button"
                        class="home-new-products-arrow home-new-products-prev"
                        data-new-products-prev
                        aria-label="Previous products"
                    >
                        ‹
                    </button>
                @endif

                <div class="home-new-products-slider" data-new-products-slider>
                    @foreach ($products as $product)
                        @include('site.partials.product-card', [
                            'product' => $product,
                            'cardKey' => 'new-product-' . $product->id,
                        ])
                    @endforeach
                </div>

                @if ($products->count() > 1)
                    <button
                        type="button"
                        class="home-new-products-arrow home-new-products-next"
                        data-new-products-next
                        aria-label="Next products"
                    >
                        ›
                    </button>
                @endif
            </div>
        </div>
    </section>
@endif