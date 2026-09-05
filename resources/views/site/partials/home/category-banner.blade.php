@php
    $enabled = $homepage['category_banner']['enabled'] ?? false;
    $isArabic = app()->getLocale() === 'ar';

    // Reuse the categories already loaded for the featured section - the two
    // that actually carry products lead the banner.
    $categories = collect($homepage['featured_categories']['items'] ?? [])
        ->filter(fn ($category) => filled($category->image))
        ->take(2);
@endphp

@if ($enabled && $categories->count() === 2)
    <section class="home-category-banner-section">
        <div class="site-container">
            <div class="home-category-banner-grid">
                @foreach ($categories as $category)
                    @php
                        $name = $category->transNow?->name
                            ?? $category->arabicTranslation?->name
                            ?? $category->englishTranslation?->name;

                        $slug = $category->englishTranslation?->slug
                            ?? $category->transNow?->slug
                            ?? $category->id;
                    @endphp

                    <a href="{{ route('site.shop', ['category' => $slug]) }}" class="home-category-banner group">
                        <img
                            src="{{ \App\Support\Media::url($category->image, 800) }}"
                            alt="{{ $name }}"
                            loading="lazy"
                        >

                        <div class="home-category-banner-overlay">
                            <span class="home-category-banner-eyebrow">
                                {{ $isArabic ? 'تشكيلة' : 'Collection' }}
                            </span>

                            <h3 class="home-category-banner-title">{{ $name }}</h3>

                            <span class="home-category-banner-cta">
                                {{ $isArabic ? 'تسوّقي الآن' : 'Shop now' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
