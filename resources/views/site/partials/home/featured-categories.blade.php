@php
    $featuredCategories = $homepage['featured_categories'] ?? [
        'enabled' => false,
        'title' => null,
        'items' => collect(),
    ];

    $categories = $featuredCategories['items'] ?? collect();
@endphp

@if (($featuredCategories['enabled'] ?? false) && $categories->count())
    <section class="home-categories-section">
        <div class="site-container">
            <div class="home-section-head">
                <span class="home-section-badge">
                    {{ app()->getLocale() === 'ar' ? 'تسوق حسب القسم' : 'Shop by Category' }}
                </span>

                <h2 class="home-section-title">
                    {{ $featuredCategories['title'] }}
                </h2>

                <p class="home-section-description">
                    {{ app()->getLocale() === 'ar'
                        ? 'اختار القسم المناسب وابدأ تصفح المنتجات بسهولة.'
                        : 'Choose your favorite category and start browsing products easily.' }}
                </p>
            </div>

            <div class="home-categories-slider-wrap">
                <button type="button" class="home-categories-arrow home-categories-prev" data-categories-prev>
                    ‹
                </button>

                <div class="home-categories-slider" data-categories-slider>
                    @foreach ($categories as $category)
                        @php
                            $name =
                                $category->transNow?->name ??
                                ($category->arabicTranslation?->name ??
                                    ($category->englishTranslation?->name ??
                                        (app()->getLocale() === 'ar' ? 'قسم' : 'Category')));

                            $description =
                                $category->transNow?->description ??
                                ($category->arabicTranslation?->description ??
                                    $category->englishTranslation?->description);

                            $categoryUrl = \Illuminate\Support\Facades\Route::has('site.categories.show')
                                ? route('site.categories.show', $category->id)
                                : '#';
                        @endphp

                        <a href="{{ route('site.shop', ['category' => $category->id]) }}"
                            class="home-category-card group">
                            <div class="home-category-image-wrap">
                                @if ($category->image)
                                    <img src="{{ \App\Support\Media::url($category->image, 500) }}" alt="{{ $name }}"
                                        class="home-category-image" loading="lazy" draggable="false">
                                @else
                                    <div class="home-category-placeholder">
                                        {{ mb_substr($name, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <div class="home-category-content">
                                <h3>{{ $name }}</h3>

                                @if ($description)
                                    <p>{{ $description }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <button type="button" class="home-categories-arrow home-categories-next" data-categories-next>
                    ›
                </button>
            </div>
        </div>
    </section>
@endif
