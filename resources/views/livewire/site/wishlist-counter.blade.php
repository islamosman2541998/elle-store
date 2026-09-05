@php
    $wishlistUrl = '#';

    if (\Illuminate\Support\Facades\Route::has('site.wishlist.index')) {
        $wishlistUrl = route('site.wishlist.index');
    } elseif (\Illuminate\Support\Facades\Route::has('site.wishlist')) {
        $wishlistUrl = route('site.wishlist');
    } elseif (\Illuminate\Support\Facades\Route::has('wishlist.index')) {
        $wishlistUrl = route('wishlist.index');
    }
@endphp

<a href="{{ $wishlistUrl }}" class="site-header-icon relative"
    aria-label="{{ app()->getLocale() === 'ar' ? 'قائمة الرغبات' : 'Wishlist' }}">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 21s-7.2-4.35-9.6-8.7C.35 8.6 2.55 4 6.75 4c2.05 0 3.45 1.05 4.25 2.05C11.8 5.05 13.2 4 15.25 4c4.2 0 6.4 4.6 4.35 8.3C19.2 16.65 12 21 12 21Z" />
    </svg>

    <span class="wishlist-counter-badge">
        {{ $count }}
    </span>
</a>