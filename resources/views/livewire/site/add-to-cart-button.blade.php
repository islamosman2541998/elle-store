<button
    type="button"
    wire:click="addToCart"
    wire:loading.attr="disabled"
    class="add-to-cart-btn {{ $added ? 'is-added' : '' }}"
    aria-label="{{ app()->getLocale() === 'ar' ? 'إضافة للسلة' : 'Add to cart' }}"
>
    <span class="add-to-cart-icon" wire:loading.remove>
        @if ($added)
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        @else
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 13L5.4 5M7 13l-2 4h13M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
            </svg>
        @endif
    </span>

    <span class="add-to-cart-icon" wire:loading>
        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
        </svg>
    </span>

    <span class="add-to-cart-text" wire:loading.remove>
        @if ($added)
            {{ app()->getLocale() === 'ar' ? 'تمت الإضافة' : 'Added' }}
        @else
            {{ app()->getLocale() === 'ar' ? 'أضف للسلة' : 'Add to cart' }}
        @endif
    </span>

    <span class="add-to-cart-text" wire:loading>
        {{ app()->getLocale() === 'ar' ? 'جاري...' : 'Adding...' }}
    </span>
</button>