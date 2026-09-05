@php
    $enabled = $homepage['features_strip']['enabled'] ?? false;
    $isArabic = app()->getLocale() === 'ar';

    $freeShippingMin = $storeSettings->global_free_shipping_enabled
        ? (float) $storeSettings->global_free_shipping_minimum
        : null;

    $currency = $storeSettings->currency_symbol ?? ($storeSettings->currency_code ?? 'EGP');

    $features = [
        [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v8H3zM14 10h4l3 3v2h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
            'title' => $isArabic ? 'شحن مجاني' : 'Free shipping',
            'text' => $freeShippingMin
                ? ($isArabic
                    ? 'للطلبات فوق ' . number_format($freeShippingMin) . ' ' . $currency
                    : 'On orders over ' . number_format($freeShippingMin) . ' ' . $currency)
                : ($isArabic ? 'على الطلبات المؤهلة' : 'On qualifying orders'),
        ],
        [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 9h11a4 4 0 1 1 0 8h-3M4 9l3-3M4 9l3 3"/>',
            'title' => $isArabic ? 'استبدال وإرجاع' : 'Easy returns',
            'text' => $isArabic ? 'خلال ١٤ يوم من الاستلام' : 'Within 14 days of delivery',
        ],
        [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18v10H3z"/><path stroke-linecap="round" d="M3 11h18"/>',
            'title' => $isArabic ? 'ادفعي عند الاستلام' : 'Cash on delivery',
            'text' => $isArabic ? 'استلمي أول، وادفعي بعدين' : 'Receive first, pay after',
        ],
        [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 9 9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>',
            'title' => $isArabic ? 'توصيل لكل المحافظات' : 'Nationwide delivery',
            'text' => $isArabic ? 'من ٢ إلى ٥ أيام عمل' : '2 to 5 working days',
        ],
    ];
@endphp

@if ($enabled)
    <section class="home-features-strip">
        <div class="site-container">
            <div class="home-features-grid">
                @foreach ($features as $feature)
                    <div class="home-feature">
                        <span class="home-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                {!! $feature['icon'] !!}
                            </svg>
                        </span>

                        <div>
                            <h3 class="home-feature-title">{{ $feature['title'] }}</h3>
                            <p class="home-feature-text">{{ $feature['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
