@php
    $bestSellers = $homepage['best_sellers'] ?? ['enabled' => false, 'title' => null, 'items' => collect()];
    $products = $bestSellers['items'] ?? collect();
    $isArabic = app()->getLocale() === 'ar';
@endphp

@if (($bestSellers['enabled'] ?? false) && $products->count())
    <section class="home-best-sellers-section">
        <div class="site-container">
            <div class="home-section-head">
                <span class="home-section-badge">
                    {{ $isArabic ? 'حسب مبيعاتنا' : 'By real sales' }}
                </span>

                <h2 class="home-section-title">
                    {{ $bestSellers['title'] }}
                </h2>

                <p class="home-section-description">
                    {{ $isArabic
                        ? 'القطع اللي عملائنا اختاروها أكتر من غيرها.'
                        : 'The pieces our customers reach for the most.' }}
                </p>
            </div>

            <div class="home-products-slider-wrap">
                @if ($products->count() > 1)
                    <button type="button" class="home-products-arrow home-products-prev" data-products-prev
                        aria-label="{{ $isArabic ? 'السابق' : 'Previous' }}">‹</button>
                @endif

                <div class="home-products-slider" data-products-slider>
                    {{-- No wrapper here: .product-card carries the slider width rules. --}}
                    @foreach ($products as $product)
                        @include('site.partials.product-card', [
                            'product' => $product,
                            'cardKey' => 'best-seller-' . $product->id,
                        ])
                    @endforeach
                </div>

                @if ($products->count() > 1)
                    <button type="button" class="home-products-arrow home-products-next" data-products-next
                        aria-label="{{ $isArabic ? 'التالي' : 'Next' }}">›</button>
                @endif
            </div>
        </div>
    </section>
@endif
