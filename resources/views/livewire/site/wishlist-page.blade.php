<section class="wishlist-page">
    <div class="site-container">
        <div class="wishlist-head">
            <div>
                <div class="wishlist-breadcrumb">
                    <a href="{{ route('site.home') }}">
                        {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                    </a>

                    <span>/</span>

                    <span>{{ app()->getLocale() === 'ar' ? 'قائمة الرغبات' : 'Wishlist' }}</span>
                </div>

                <h1>{{ app()->getLocale() === 'ar' ? 'قائمة الرغبات' : 'Wishlist' }}</h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'كل المنتجات التي أعجبتك محفوظة هنا لتعود إليها لاحقًا.'
                        : 'All products you liked are saved here so you can come back to them later.' }}
                </p>
            </div>

            <a href="{{ route('site.shop') }}">
                {{ app()->getLocale() === 'ar' ? 'استكمال التسوق' : 'Continue Shopping' }}
            </a>
        </div>

        @if ($items->count())
            <div class="wishlist-grid">
                @foreach ($items as $item)
                    @php
                        $product = $item->product;

                        $translation = $product?->transNow
                            ?? $product?->arabicTranslation
                            ?? $product?->englishTranslation;

                        $name = $translation?->name ?? (app()->getLocale() === 'ar' ? 'منتج' : 'Product');

                        $image = $product?->main_image;

                        $productUrl = $product && $translation?->slug
                            ? route('site.products.show', $translation->slug)
                            : '#';
                    @endphp

                    @if ($product)
                        <div class="wishlist-item-card">
                            <a href="{{ $productUrl }}" class="wishlist-item-image">
                                @if ($image)
                                    <img src="{{ \App\Support\Media::url($image, 500) }}" alt="{{ $name }}" loading="lazy">
                                @else
                                    <span>{{ mb_substr($name, 0, 1) }}</span>
                                @endif
                            </a>

                            <div class="wishlist-item-content">
                                <a href="{{ $productUrl }}">
                                    <h3>{{ $name }}</h3>
                                </a>

                                <div class="wishlist-item-actions">
                                    <a href="{{ $productUrl }}" class="wishlist-view-btn">
                                        {{ app()->getLocale() === 'ar' ? 'عرض المنتج' : 'View Product' }}
                                    </a>

                                    <button type="button" wire:click="removeFromWishlist({{ $item->id }})">
                                        {{ app()->getLocale() === 'ar' ? 'حذف' : 'Remove' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="wishlist-empty">
                <div>
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 21s-7.2-4.35-9.6-8.7C.35 8.6 2.55 4 6.75 4c2.05 0 3.45 1.05 4.25 2.05C11.8 5.05 13.2 4 15.25 4c4.2 0 6.4 4.6 4.35 8.3C19.2 16.65 12 21 12 21Z" />
                    </svg>
                </div>

                <h2>{{ app()->getLocale() === 'ar' ? 'قائمة الرغبات فارغة' : 'Your wishlist is empty' }}</h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'ابدأ بإضافة المنتجات التي تعجبك إلى قائمة الرغبات.'
                        : 'Start adding products you like to your wishlist.' }}
                </p>

                <a href="{{ route('site.shop') }}">
                    {{ app()->getLocale() === 'ar' ? 'تصفح المنتجات' : 'Browse Products' }}
                </a>
            </div>
        @endif
    </div>
</section>