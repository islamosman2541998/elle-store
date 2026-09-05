<button
    type="button"
    wire:click="toggleWishlist"
    wire:loading.attr="disabled"
    class="wishlist-btn {{ $wished ? 'is-active' : '' }}"
    aria-label="{{ app()->getLocale() === 'ar' ? 'المفضلة' : 'Wishlist' }}"
>
    <span wire:loading.remove>
        @if ($wished)
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21s-7.2-4.35-9.6-8.7C.35 8.6 2.55 4 6.75 4c2.05 0 3.45 1.05 4.25 2.05C11.8 5.05 13.2 4 15.25 4c4.2 0 6.4 4.6 4.35 8.3C19.2 16.65 12 21 12 21Z" />
            </svg>
        @else
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7.2-4.35-9.6-8.7C.35 8.6 2.55 4 6.75 4c2.05 0 3.45 1.05 4.25 2.05C11.8 5.05 13.2 4 15.25 4c4.2 0 6.4 4.6 4.35 8.3C19.2 16.65 12 21 12 21Z" />
            </svg>
        @endif
    </span>

    <span wire:loading>
        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
        </svg>
    </span>
</button>