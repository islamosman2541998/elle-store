@php
    $brandsSection = $homepage['brands'] ?? [
        'enabled' => false,
        'title' => null,
        'items' => collect(),
    ];

    $brands = $brandsSection['items'] ?? collect();
@endphp

@if (($brandsSection['enabled'] ?? false) && $brands->count())
    <section class="home-brands-section">
        <div class="site-container">
            <div class="home-section-head">
                <span class="home-section-badge">
                    {{ app()->getLocale() === 'ar' ? 'تسوق حسب البراند' : 'Shop by Brand' }}
                </span>

                <h2 class="home-section-title">
                    {{ $brandsSection['title'] }}
                </h2>

                <p class="home-section-description">
                    {{ app()->getLocale() === 'ar'
                        ? 'اكتشف أفضل العلامات التجارية المتاحة داخل المتجر.'
                        : 'Discover the top brands available in our store.' }}
                </p>
            </div>

            <div class="home-brands-slider-wrap">
                @if ($brands->count() > 1)
                    <button
                        type="button"
                        class="home-brands-arrow home-brands-prev"
                        data-brands-prev
                        aria-label="Previous brands"
                    >
                        ‹
                    </button>
                @endif

                <div class="home-brands-slider" data-brands-slider>
                    @foreach ($brands as $brand)
                        @php
                            $name =
                                $brand->transNow?->name ??
                                $brand->arabicTranslation?->name ??
                                $brand->englishTranslation?->name ??
                                (app()->getLocale() === 'ar' ? 'براند' : 'Brand');

                            $description =
                                $brand->transNow?->description ??
                                $brand->arabicTranslation?->description ??
                                $brand->englishTranslation?->description;

                            $brandUrl = \Illuminate\Support\Facades\Route::has('site.brands.show')
                                ? route('site.brands.show', $brand->id)
                                : '#';
                        @endphp

                        <a href="{{ route('site.shop', ['brand' => $brand->id]) }}" class="home-brand-card group">
                            <div class="home-brand-logo-wrap">
                                @if ($brand->logo)
                                    <img
                                        src="{{ asset('storage/' . $brand->logo) }}"
                                        alt="{{ $name }}"
                                        class="home-brand-logo"
                                        loading="lazy"
                                        draggable="false"
                                    >
                                @else
                                    <div class="home-brand-placeholder">
                                        {{ mb_substr($name, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <div class="home-brand-content">
                                <h3>
                                    {{ $name }}
                                </h3>

                                @if ($description)
                                    <p>
                                        {{ $description }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($brands->count() > 1)
                    <button
                        type="button"
                        class="home-brands-arrow home-brands-next"
                        data-brands-next
                        aria-label="Next brands"
                    >
                        ›
                    </button>
                @endif
            </div>
        </div>
    </section>
@endif