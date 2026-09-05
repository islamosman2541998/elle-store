<section class="shop-page">
    <div class="site-container">
        <div class="shop-hero">
            <div>
                <div class="shop-breadcrumb">
                    <a href="{{ route('site.home') }}" wire:navigate>
                        {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                    </a>

                    <span>/</span>

                    <span>
                        {{ app()->getLocale() === 'ar' ? 'المتجر' : 'Shop' }}
                    </span>
                </div>

                <h1>
                    {{ app()->getLocale() === 'ar' ? 'المتجر' : 'Shop' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'اكتشف أحدث المنتجات، العروض، والتشكيلات المختارة بعناية.'
                        : 'Explore our latest products, offers, and carefully selected collections.' }}
                </p>
            </div>
        </div>

        <div class="shop-mobile-toolbar">
            <div class="shop-mobile-search">
                <input type="search" wire:model.live.debounce.400ms="q"
                    placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن منتج' : 'Search products' }}">

                <button type="button">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6Z" />
                    </svg>
                </button>
            </div>

            <div class="shop-mobile-actions">
                <button type="button" wire:click="openMobileFilters">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M6 12h12M10 18h4" />
                    </svg>

                    {{ app()->getLocale() === 'ar' ? 'فلتر' : 'Filter' }}
                </button>

                <select wire:model.live="sort">
                    <option value="newest">
                        {{ app()->getLocale() === 'ar' ? 'الأحدث' : 'Newest' }}
                    </option>

                    <option value="price_asc">
                        {{ app()->getLocale() === 'ar' ? 'الأقل سعرًا' : 'Price low to high' }}
                    </option>

                    <option value="price_desc">
                        {{ app()->getLocale() === 'ar' ? 'الأعلى سعرًا' : 'Price high to low' }}
                    </option>

                    <option value="featured">
                        {{ app()->getLocale() === 'ar' ? 'المميز' : 'Featured' }}
                    </option>
                </select>
            </div>
        </div>

        @if ($q || $category || $brand || $min_price || $max_price || $sale || $featured)
            <div class="shop-active-filters">
                @if ($q)
                    <button type="button" wire:click="removeFilter('q')">
                        {{ app()->getLocale() === 'ar' ? 'بحث:' : 'Search:' }} {{ $q }}
                        <span>×</span>
                    </button>
                @endif

                @if ($category)
                    <button type="button" wire:click="removeFilter('category')">
                        {{ app()->getLocale() === 'ar' ? 'قسم محدد' : 'Category selected' }}
                        <span>×</span>
                    </button>
                @endif

                @if ($brand)
                    <button type="button" wire:click="removeFilter('brand')">
                        {{ app()->getLocale() === 'ar' ? 'براند محدد' : 'Brand selected' }}
                        <span>×</span>
                    </button>
                @endif
                @if ($min_price || $max_price)
                    <button type="button" wire:click="clearPriceFilter">
                        {{ app()->getLocale() === 'ar' ? 'السعر:' : 'Price:' }}
                        {{ number_format($selectedMinPrice) }}
                        -
                        {{ number_format($selectedMaxPrice) }}
                        {{ $storeSettings->currency_symbol ?? 'EGP' }}
                        <span>×</span>
                    </button>
                @endif
                @if ($sale)
                    <button type="button" wire:click="removeFilter('sale')">
                        {{ app()->getLocale() === 'ar' ? 'عروض فقط' : 'Sale only' }}
                        <span>×</span>
                    </button>
                @endif
                @if ($featured)
                    <button type="button" wire:click="removeFilter('featured')">
                        {{ app()->getLocale() === 'ar' ? 'منتجات مميزة' : 'Featured products' }}
                        <span>×</span>
                    </button>
                @endif
                <button type="button" class="clear-all" wire:click="clearFilters">
                    {{ app()->getLocale() === 'ar' ? 'مسح الكل' : 'Clear all' }}
                </button>
            </div>
        @endif

        <div class="shop-layout">
            <aside class="shop-sidebar">
                @include('livewire.site.partials.shop-filters')
            </aside>

            <div class="shop-content">
                <div id="shop-products-top" class="shop-scroll-anchor"></div>

                <div class="shop-toolbar">
                    <div>
                        <strong>{{ $products->total() }}</strong>

                        <span>
                            {{ app()->getLocale() === 'ar' ? 'منتج' : 'Products' }}
                        </span>
                    </div>

                    <div class="shop-desktop-search">
                        <input type="search" wire:model.live.debounce.400ms="q"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن منتج' : 'Search products' }}">
                    </div>

                    <select wire:model.live="sort">
                        <option value="newest">
                            {{ app()->getLocale() === 'ar' ? 'الأحدث' : 'Newest' }}
                        </option>

                        <option value="price_asc">
                            {{ app()->getLocale() === 'ar' ? 'الأقل سعرًا' : 'Price low to high' }}
                        </option>

                        <option value="price_desc">
                            {{ app()->getLocale() === 'ar' ? 'الأعلى سعرًا' : 'Price high to low' }}
                        </option>

                        <option value="featured">
                            {{ app()->getLocale() === 'ar' ? 'المميز' : 'Featured' }}
                        </option>
                    </select>
                </div>
               @if (collect($subCategories ?? [])->count())
    <div class="shop-child-categories">
        <div class="shop-child-categories-head">
            <span>
                {{ app()->getLocale() === 'ar' ? 'الأقسام الفرعية' : 'Sub Categories' }}
            </span>

            @if ($selectedCategory?->parent_id)
                <button type="button" wire:click="selectChildCategory({{ $selectedCategory->parent_id }})">
                    {{ app()->getLocale() === 'ar' ? 'عرض كل القسم' : 'View Parent' }}
                </button>
            @else
                <button type="button" wire:click="removeFilter('category')">
                    {{ app()->getLocale() === 'ar' ? 'عرض كل المنتجات' : 'View All Products' }}
                </button>
            @endif
        </div>

        <div class="shop-child-categories-list">
            @foreach (collect($subCategories ?? []) as $childCategory)
                @php
                    $childTranslation = $childCategory->transNow
                        ?? $childCategory->arabicTranslation
                        ?? $childCategory->englishTranslation;

                    $childName = $childTranslation?->name ?? 'Category #' . $childCategory->id;
                @endphp

                <button
                    type="button"
                    wire:key="shop-child-category-{{ $childCategory->id }}"
                    wire:click="selectChildCategory({{ $childCategory->id }})"
                    class="{{ (int) $selectedCategory?->id === (int) $childCategory->id ? 'is-active' : '' }}"
                >
                    {{ $childName }}
                </button>
            @endforeach
        </div>
    </div>
@endif
                <div wire:loading.class="is-loading" class="shop-results-wrap">
                    <div wire:loading.flex class="shop-loading">
                        <span></span>
                        <p>
                            {{ app()->getLocale() === 'ar' ? 'جاري تحميل المنتجات...' : 'Loading products...' }}
                        </p>
                    </div>

                    @if ($products->count())
                        <div class="shop-products-grid">
                            @foreach ($products as $product)
                                <div wire:key="shop-product-{{ $product->id }}">
                                    @include('site.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>

                        @if ($products->hasPages())
                            @php
                                $currentPage = $products->currentPage();
                                $lastPage = $products->lastPage();
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($lastPage, $currentPage + 2);
                            @endphp

                            <div class="shop-live-pagination">
                                <button type="button" wire:click="previousShopPage" wire:loading.attr="disabled"
                                    @disabled($products->onFirstPage()) class="shop-page-btn">
                                    {{ app()->getLocale() === 'ar' ? 'السابق' : 'Previous' }}
                                </button>

                                @if ($startPage > 1)
                                    <button type="button" wire:click="goToShopPage(1)" wire:loading.attr="disabled"
                                        class="shop-page-number">
                                        1
                                    </button>

                                    @if ($startPage > 2)
                                        <span class="shop-page-dots">...</span>
                                    @endif
                                @endif

                                @for ($page = $startPage; $page <= $endPage; $page++)
                                    <button type="button" wire:key="shop-page-{{ $page }}"
                                        wire:click="goToShopPage({{ $page }})" wire:loading.attr="disabled"
                                        class="shop-page-number {{ $currentPage === $page ? 'is-active' : '' }}">
                                        {{ $page }}
                                    </button>
                                @endfor

                                @if ($endPage < $lastPage)
                                    @if ($endPage < $lastPage - 1)
                                        <span class="shop-page-dots">...</span>
                                    @endif

                                    <button type="button" wire:click="goToShopPage({{ $lastPage }})"
                                        wire:loading.attr="disabled" class="shop-page-number">
                                        {{ $lastPage }}
                                    </button>
                                @endif

                                <button type="button" wire:click="nextShopPage" wire:loading.attr="disabled"
                                    @disabled(!$products->hasMorePages()) class="shop-page-btn">
                                    {{ app()->getLocale() === 'ar' ? 'التالي' : 'Next' }}
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="shop-empty">
                            <div class="shop-empty-icon">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3h18M5 7h14l-1 13H6L5 7Z" />
                                </svg>
                            </div>

                            <h3>
                                {{ app()->getLocale() === 'ar' ? 'لا توجد منتجات' : 'No products found' }}
                            </h3>

                            <p>
                                {{ app()->getLocale() === 'ar'
                                    ? 'جرّب تغيير الفلاتر أو مسح البحث الحالي.'
                                    : 'Try changing filters or clearing your current search.' }}
                            </p>

                            <button type="button" wire:click="clearFilters">
                                {{ app()->getLocale() === 'ar' ? 'مسح الفلاتر' : 'Clear filters' }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="shop-filter-drawer {{ $showMobileFilters ? 'is-open' : '' }}">
        <button type="button" class="shop-filter-backdrop" wire:click="closeMobileFilters"></button>

        <div class="shop-filter-panel">
            <div class="shop-filter-panel-head">
                <h3>
                    {{ app()->getLocale() === 'ar' ? 'فلترة المنتجات' : 'Filter products' }}
                </h3>

                <button type="button" wire:click="closeMobileFilters">
                    ×
                </button>
            </div>

            @include('livewire.site.partials.shop-filters')
        </div>
    </div>
</section>
@script
    <script>
        $wire.on('shop-scroll-to-products', function() {
            setTimeout(function() {
                const target = document.getElementById('shop-products-top');

                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 120);
        });
    </script>
@endscript
