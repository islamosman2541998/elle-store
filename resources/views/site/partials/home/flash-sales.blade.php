@php
    $flashSales = $homepage['flash_sales'] ?? [
        'enabled' => false,
        'title' => null,
        'items' => collect(),
    ];

    $products = $flashSales['items'] ?? collect();

    $flashSalesUrl = route('site.shop', ['sale' => 1]);
@endphp

@if (($flashSales['enabled'] ?? false) && $products->count())
    <section class="home-flash-sales-section">
        <div class="site-container">
            <div class="home-flash-sales-head">
                <div>
                    <span class="home-flash-badge">
                        {{ app()->getLocale() === 'ar' ? 'عروض محدودة' : 'Limited Offers' }}
                    </span>

                    <h2 class="home-flash-title">
                        {{ $flashSales['title'] }}
                    </h2>

                    <p class="home-flash-description">
                        {{ app()->getLocale() === 'ar'
                            ? 'استفد من أقوى الخصومات قبل انتهاء الكمية أو مدة العرض.'
                            : 'Catch the best deals before stock or offer time runs out.' }}
                    </p>
                </div>

                <div class="home-flash-label">
                    <span>
                        {{ app()->getLocale() === 'ar' ? 'خصومات الآن' : 'Live Deals' }}
                    </span>

                    <a
                        href="{{ $flashSalesUrl }}"
                        class="mt-3 inline-flex items-center justify-center rounded-full bg-white px-5 py-2 text-xs font-black text-dark transition hover:bg-brand hover:text-white"
                    >
                        {{ app()->getLocale() === 'ar' ? 'عرض كل العروض' : 'View all deals' }}
                    </a>
                </div>
            </div>

            <div class="home-flash-sales-slider-wrap">
                @if ($products->count() > 1)
                    <button
                        type="button"
                        class="home-flash-sales-arrow home-flash-sales-prev"
                        data-flash-sales-prev
                        aria-label="Previous flash sales"
                    >
                        ‹
                    </button>
                @endif

                <div class="home-flash-sales-slider" data-flash-sales-slider>
                    @foreach ($products as $product)
                        @include('site.partials.product-card', [
                            'product' => $product,
                            'cardKey' => 'flash-product-' . $product->id,
                        ])
                    @endforeach
                </div>

                @if ($products->count() > 1)
                    <button
                        type="button"
                        class="home-flash-sales-arrow home-flash-sales-next"
                        data-flash-sales-next
                        aria-label="Next flash sales"
                    >
                        ›
                    </button>
                @endif
            </div>
        </div>
    </section>
@endif