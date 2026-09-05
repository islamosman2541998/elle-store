@php
    $currency = $storeSettings->currency_symbol ?? $storeSettings->currency_code ?? 'EGP';

    $subtotal = $cart ? (float) $cart->subtotal : 0;
    $discountTotal = $cart ? (float) $cart->discount_total : 0;
    $shippingTotal = $cart ? (float) $cart->shipping_total : 0;
    $taxTotal = $cart ? (float) $cart->tax_total : 0;
    $grandTotal = $cart ? (float) $cart->grand_total : 0;

    $checkoutUrl = \Illuminate\Support\Facades\Route::has('site.checkout')
        ? route('site.checkout')
        : '#';
@endphp

<section class="cart-page">
    <div class="site-container">
        <div class="cart-page-head">
            <div>
                <div class="cart-breadcrumb">
                    <a href="{{ route('site.home') }}">
                        {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                    </a>

                    <span>/</span>

                    <span>
                        {{ app()->getLocale() === 'ar' ? 'السلة' : 'Cart' }}
                    </span>
                </div>

                <h1>
                    {{ app()->getLocale() === 'ar' ? 'سلة التسوق' : 'Shopping Cart' }}
                </h1>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'راجع المنتجات المختارة قبل إتمام الطلب.'
                        : 'Review your selected items before checkout.' }}
                </p>
            </div>

            @if ($items->count())
                <button type="button" wire:click="clearCart" class="cart-clear-btn">
                    {{ app()->getLocale() === 'ar' ? 'تفريغ السلة' : 'Clear cart' }}
                </button>
            @endif
        </div>

        @if ($items->count())
            <div class="cart-layout">
                <div class="cart-items-list">
                    @foreach ($items as $item)
                        @php
                            $snapshot = $item->snapshot ?? [];

                            $productSnapshot = $snapshot['product'] ?? [];
                            $variantSnapshot = $snapshot['variant'] ?? [];

                            $productName =
                                $productSnapshot['name']
                                ?? $item->product?->transNow?->name
                                ?? $item->product?->arabicTranslation?->name
                                ?? $item->product?->englishTranslation?->name
                                ?? (app()->getLocale() === 'ar' ? 'منتج' : 'Product');

                            $variantName =
                                $variantSnapshot['name']
                                ?? $item->variant?->transNow?->name
                                ?? $item->variant?->arabicTranslation?->name
                                ?? $item->variant?->englishTranslation?->name
                                ?? null;

                            $image =
                                $variantSnapshot['image']
                                ?? $productSnapshot['image']
                                ?? $item->variant?->image
                                ?? $item->product?->main_image
                                ?? null;

                            $sku =
                                $variantSnapshot['sku']
                                ?? $productSnapshot['sku']
                                ?? $item->variant?->sku
                                ?? $item->product?->sku
                                ?? null;

                            $attributes = $variantSnapshot['attributes'] ?? [];
                        @endphp

                        <div class="cart-item-card" wire:key="cart-item-{{ $item->id }}">
                            <div class="cart-item-image">
                                @if ($image)
                                    <img
                                        src="{{ \App\Support\Media::url($image, 500) }}"
                                        alt="{{ $productName }}"
                                        draggable="false"
                                    >
                                @else
                                    <div>
                                        {{ mb_substr($productName, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <div class="cart-item-content">
                                <div class="cart-item-title-row">
                                    <div>
                                        <h3>
                                            {{ $productName }}
                                        </h3>

                                        @if ($variantName)
                                            <p>
                                                {{ $variantName }}
                                            </p>
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $item->id }})"
                                        class="cart-item-remove"
                                        aria-label="{{ app()->getLocale() === 'ar' ? 'حذف' : 'Remove' }}"
                                    >
                                        ×
                                    </button>
                                </div>

                                @if ($attributes)
                                    <div class="cart-item-attributes">
                                        @foreach ($attributes as $attribute)
                                            <span>
                                                {{ $attribute['attribute_name'] ?? '' }}:
                                                {{ $attribute['value_name'] ?? '' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($sku)
                                    <div class="cart-item-sku">
                                        {{ app()->getLocale() === 'ar' ? 'الكود:' : 'SKU:' }}
                                        {{ $sku }}
                                    </div>
                                @endif

                                <div class="cart-item-bottom">
                                    <div class="cart-item-qty">
                                        <button
                                            type="button"
                                            wire:click="decreaseQuantity({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            -
                                        </button>

                                        <span>
                                            {{ $item->quantity }}
                                        </span>

                                        <button
                                            type="button"
                                            wire:click="increaseQuantity({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <div class="cart-item-prices">
                                        <span>
                                            {{ number_format((float) $item->unit_price, 2) }} {{ $currency }}
                                        </span>

                                        <strong>
                                            {{ number_format((float) $item->subtotal, 2) }} {{ $currency }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <aside class="cart-summary">
                    <h2>
                        {{ app()->getLocale() === 'ar' ? 'ملخص الطلب' : 'Order Summary' }}
                    </h2>

                    <div class="cart-summary-lines">
                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي الفرعي' : 'Subtotal' }}</span>
                            <strong>{{ number_format($subtotal, 2) }} {{ $currency }}</strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الخصم' : 'Discount' }}</span>
                            <strong>{{ number_format($discountTotal, 2) }} {{ $currency }}</strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الشحن' : 'Shipping' }}</span>
                            <strong>
                                {{ $shippingTotal > 0 ? number_format($shippingTotal, 2) . ' ' . $currency : (app()->getLocale() === 'ar' ? 'يُحسب لاحقًا' : 'Calculated later') }}
                            </strong>
                        </div>

                        <div>
                            <span>{{ app()->getLocale() === 'ar' ? 'الضريبة' : 'Tax' }}</span>
                            <strong>{{ number_format($taxTotal, 2) }} {{ $currency }}</strong>
                        </div>
                    </div>

                    <div class="cart-summary-total">
                        <span>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</span>
                        <strong>{{ number_format($grandTotal, 2) }} {{ $currency }}</strong>
                    </div>

                    <a href="{{ $checkoutUrl }}" class="cart-checkout-btn">
                        {{ app()->getLocale() === 'ar' ? 'إتمام الطلب' : 'Checkout' }}
                    </a>

                    <a href="{{ route('site.shop') }}" class="cart-continue-btn">
                        {{ app()->getLocale() === 'ar' ? 'متابعة التسوق' : 'Continue shopping' }}
                    </a>
                </aside>
            </div>
        @else
            <div class="cart-empty">
                <div>
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-2 4h13M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
                    </svg>
                </div>

                <h2>
                    {{ app()->getLocale() === 'ar' ? 'السلة فارغة' : 'Your cart is empty' }}
                </h2>

                <p>
                    {{ app()->getLocale() === 'ar'
                        ? 'ابدأ التسوق وأضف منتجاتك المفضلة إلى السلة.'
                        : 'Start shopping and add your favorite products to the cart.' }}
                </p>

                <a href="{{ route('site.shop') }}">
                    {{ app()->getLocale() === 'ar' ? 'ابدأ التسوق' : 'Start shopping' }}
                </a>
            </div>
        @endif
    </div>
</section>