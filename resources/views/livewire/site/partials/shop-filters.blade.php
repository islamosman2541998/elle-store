<div class="shop-filter-form">
    <div class="shop-filter-block">
        <h4>
            {{ app()->getLocale() === 'ar' ? 'الأقسام' : 'Categories' }}
        </h4>

        <div class="shop-filter-list">
            <label class="shop-filter-option">
                <input
                    type="radio"
                    wire:model.live="category"
                    value=""
                >

                <span>
                    {{ app()->getLocale() === 'ar' ? 'كل الأقسام' : 'All categories' }}
                </span>
            </label>

            @foreach ($categories as $categoryItem)
                @php
                    $categoryName =
                        $categoryItem->transNow?->name ??
                        $categoryItem->arabicTranslation?->name ??
                        $categoryItem->englishTranslation?->name ??
                        (app()->getLocale() === 'ar' ? 'قسم' : 'Category');
                @endphp

                <label class="shop-filter-option" wire:key="filter-category-{{ $categoryItem->id }}">
                    <input
                        type="radio"
                        wire:model.live="category"
                        value="{{ $categoryItem->id }}"
                    >

                    <span>{{ $categoryName }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="shop-filter-block">
        <h4>
            {{ app()->getLocale() === 'ar' ? 'البراندات' : 'Brands' }}
        </h4>

        <div class="shop-filter-list">
            <label class="shop-filter-option">
                <input
                    type="radio"
                    wire:model.live="brand"
                    value=""
                >

                <span>
                    {{ app()->getLocale() === 'ar' ? 'كل البراندات' : 'All brands' }}
                </span>
            </label>

            @foreach ($brands as $brandItem)
                @php
                    $brandName =
                        $brandItem->transNow?->name ??
                        $brandItem->arabicTranslation?->name ??
                        $brandItem->englishTranslation?->name ??
                        (app()->getLocale() === 'ar' ? 'براند' : 'Brand');
                @endphp

                <label class="shop-filter-option" wire:key="filter-brand-{{ $brandItem->id }}">
                    <input
                        type="radio"
                        wire:model.live="brand"
                        value="{{ $brandItem->id }}"
                    >

                    <span>{{ $brandName }}</span>
                </label>
            @endforeach
        </div>
    </div>

   <div class="shop-filter-block">
    <h4>
        {{ app()->getLocale() === 'ar' ? 'السعر' : 'Price' }}
    </h4>

    <div class="shop-price-values">
        <div>
            <span>{{ app()->getLocale() === 'ar' ? 'أقل سعر' : 'Min' }}</span>
            <strong>
                {{ number_format($selectedMinPrice) }}
                {{ $storeSettings->currency_symbol ?? 'EGP' }}
            </strong>
        </div>

        <div>
            <span>{{ app()->getLocale() === 'ar' ? 'أعلى سعر' : 'Max' }}</span>
            <strong>
                {{ number_format($selectedMaxPrice) }}
                {{ $storeSettings->currency_symbol ?? 'EGP' }}
            </strong>
        </div>
    </div>

    <div
        class="shop-price-range"
        style="
            --range-left: {{ $priceRangeLeft }}%;
            --range-right: {{ $priceRangeRight }}%;
        "
    >
        <div class="shop-price-range-track">
            <div class="shop-price-range-selected"></div>
        </div>

        <input
            type="range"
            min="{{ $priceFloor }}"
            max="{{ $priceCeiling }}"
            step="1"
            value="{{ $selectedMinPrice }}"
            wire:input.debounce.250ms="setMinPrice($event.target.value)"
        >

        <input
            type="range"
            min="{{ $priceFloor }}"
            max="{{ $priceCeiling }}"
            step="1"
            value="{{ $selectedMaxPrice }}"
            wire:input.debounce.250ms="setMaxPrice($event.target.value)"
        >
    </div>

    @if ($min_price || $max_price)
        <button
            type="button"
            wire:click="clearPriceFilter"
            class="shop-price-clear"
        >
            {{ app()->getLocale() === 'ar' ? 'مسح فلتر السعر' : 'Clear price filter' }}
        </button>
    @endif
</div>

    <div class="shop-filter-block">
        <label class="shop-filter-sale">
            <input
                type="checkbox"
                wire:model.live="sale"
            >

            <span>
                {{ app()->getLocale() === 'ar' ? 'العروض فقط' : 'Sale only' }}
            </span>
        </label>
    </div>

    <div class="shop-filter-actions">
        <button type="button" wire:click="closeMobileFilters">
            {{ app()->getLocale() === 'ar' ? 'تطبيق الفلتر' : 'Apply filters' }}
        </button>

        <button type="button" class="shop-filter-clear-btn" wire:click="clearFilters">
            {{ app()->getLocale() === 'ar' ? 'مسح' : 'Clear' }}
        </button>
    </div>
</div>