@php
    $currency = $storeSettings->currency_symbol ?? ($storeSettings->currency_code ?? 'EGP');
    $isArabic = app()->getLocale() === 'ar';
@endphp

<div>
    @if ($open && $product)
        <div
            class="quick-view-overlay"
            wire:key="quick-view-{{ $product->id }}"
            x-data
            x-on:keydown.escape.window="$wire.closeQuickView()"
        >
            {{-- Clicking the backdrop closes the dialog. --}}
            <div class="quick-view-backdrop" wire:click="closeQuickView"></div>

            <div class="quick-view-dialog" role="dialog" aria-modal="true">
                <button
                    type="button"
                    class="quick-view-close"
                    wire:click="closeQuickView"
                    aria-label="{{ $isArabic ? 'إغلاق' : 'Close' }}"
                >
                    &times;
                </button>

                <div class="quick-view-body">
                    {{-- Gallery --}}
                    <div class="quick-view-gallery">
                        <div class="quick-view-main-image">
                            @if ($mainImageUrl)
                                <img src="{{ $mainImageUrl }}" alt="{{ $translation?->name }}" draggable="false">
                            @endif

                            @if ($priceData['has_sale'])
                                <span class="quick-view-sale-badge">
                                    -{{ $priceData['discount_percentage'] }}%
                                </span>
                            @endif
                        </div>

                        @if ($galleryImages->count() > 1)
                            <div class="quick-view-thumbs">
                                @foreach ($galleryImages as $image)
                                    <button
                                        type="button"
                                        wire:key="qv-thumb-{{ $loop->index }}"
                                        wire:click="setMainImage('{{ $image['path'] }}')"
                                        class="{{ $selectedImage === $image['path'] ? 'is-selected' : '' }}"
                                    >
                                        <img src="{{ $image['url'] }}" alt="{{ $translation?->name }}" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="quick-view-details">
                        <h2 class="quick-view-title">{{ $translation?->name }}</h2>

                        @if ($translation?->short_description)
                            <p class="quick-view-description">{{ $translation->short_description }}</p>
                        @endif

                        <div class="quick-view-prices">
                            <span class="quick-view-price">
                                {{ number_format($priceData['final_price'], 2) }} {{ $currency }}
                            </span>

                            @if ($priceData['has_sale'])
                                <span class="quick-view-old-price">
                                    {{ number_format($priceData['original_price'], 2) }} {{ $currency }}
                                </span>
                            @endif
                        </div>

                        @if ($variantGroups->count())
                            <div class="product-show-variants">
                                @foreach ($variantGroups as $group)
                                    <div class="product-show-variant-group" wire:key="qv-group-{{ $group['id'] }}">
                                        <h4>{{ $group['name'] }}</h4>

                                        <div class="product-show-variant-values">
                                            @foreach ($group['values'] as $value)
                                                @php
                                                    $isSelected = ($selectedAttributes[$group['id']] ?? null) == $value['id'];
                                                @endphp

                                                <button
                                                    type="button"
                                                    wire:key="qv-value-{{ $group['id'] }}-{{ $value['id'] }}"
                                                    wire:click="selectAttributeValue('{{ $group['id'] }}', '{{ $value['id'] }}')"
                                                    class="{{ $isSelected ? 'is-selected' : '' }}"
                                                >
                                                    @if ($value['color'])
                                                        <span
                                                            class="quick-view-swatch"
                                                            style="background: {{ $value['color'] }}"
                                                        ></span>
                                                    @endif

                                                    {{ $value['name'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="quick-view-stock {{ $inStock ? 'is-in' : 'is-out' }}">
                            @if ($inStock)
                                {{ $isArabic ? 'متوفر' : 'In stock' }}
                            @else
                                {{ $isArabic ? 'نفد المخزون' : 'Out of stock' }}
                            @endif
                        </div>

                        <div class="quick-view-quantity">
                            <span>{{ $isArabic ? 'الكمية' : 'Quantity' }}</span>

                            <div>
                                <button type="button" wire:click="decreaseQuantity">-</button>
                                <input type="number" min="1" wire:model.live.debounce.300ms="quantity">
                                <button type="button" wire:click="increaseQuantity">+</button>
                            </div>
                        </div>

                        <div class="quick-view-actions">
                            <button
                                type="button"
                                class="quick-view-add-btn"
                                wire:click="addToCart"
                                wire:loading.attr="disabled"
                                @disabled(! $inStock)
                            >
                                <span wire:loading.remove wire:target="addToCart">
                                    {{ $isArabic ? 'أضف للسلة' : 'Add to cart' }}
                                </span>

                                <span wire:loading wire:target="addToCart">
                                    {{ $isArabic ? 'جاري...' : 'Adding...' }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="quick-view-wishlist-btn {{ $wished ? 'is-active' : '' }}"
                                wire:click="toggleWishlist"
                                wire:loading.attr="disabled"
                                aria-label="{{ $isArabic ? 'المفضلة' : 'Wishlist' }}"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24"
                                    fill="{{ $wished ? 'currentColor' : 'none' }}"
                                    stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21s-7.2-4.35-9.6-8.7C.35 8.6 2.55 4 6.75 4c2.05 0 3.45 1.05 4.25 2.05C11.8 5.05 13.2 4 15.25 4c4.2 0 6.4 4.6 4.35 8.3C19.2 16.65 12 21 12 21Z" />
                                </svg>

                                <span>
                                    {{ $wished
                                        ? ($isArabic ? 'في المفضلة' : 'In wishlist')
                                        : ($isArabic ? 'المفضلة' : 'Wishlist') }}
                                </span>
                            </button>
                        </div>

                        <a href="{{ route('site.products.show', $translation?->slug ?? $product->id) }}"
                            class="quick-view-full-link">
                            {{ $isArabic ? 'عرض تفاصيل المنتج كاملة' : 'View full product details' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
