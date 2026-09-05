@php
    $locale = app()->getLocale();
    $arabic = $locale === 'ar';

    $title = $arabic ? $storeSettings->coupon_popup_title_ar : $storeSettings->coupon_popup_title_en;
    $text = $arabic ? $storeSettings->coupon_popup_text_ar : $storeSettings->coupon_popup_text_en;
    $buttonLabel = $arabic ? $storeSettings->coupon_popup_button_label_ar : $storeSettings->coupon_popup_button_label_en;
    $code = trim((string) $storeSettings->coupon_popup_code);

    $delay = max(0, (int) ($storeSettings->coupon_popup_delay ?? 4)) * 1000;
    $rememberDays = max(0, (int) ($storeSettings->coupon_popup_remember_days ?? 7));

    $image = $storeSettings->coupon_popup_image
        ? asset('storage/' . $storeSettings->coupon_popup_image)
        : null;
@endphp

@if ($storeSettings->coupon_popup_enabled && (filled($title) || filled($code)))
    <div
        x-data="{
            open: false,
            copied: false,
            key: 'elle_coupon_popup_dismissed',

            init() {
                if (this.dismissedRecently()) {
                    return;
                }

                setTimeout(() => { this.open = true }, {{ $delay }});
            },

            /* Per browser, and only for as long as the shop chose. */
            dismissedRecently() {
                if ({{ $rememberDays }} === 0) {
                    return false;
                }

                try {
                    const until = window.localStorage.getItem(this.key);

                    return until && Number(until) > Date.now();
                } catch (e) {
                    return false;
                }
            },

            close() {
                this.open = false;

                try {
                    window.localStorage.setItem(
                        this.key,
                        String(Date.now() + {{ $rememberDays }} * 86400000)
                    );
                } catch (e) {
                    /* Private browsing: the popup simply shows again. */
                }
            },

            copy() {
                navigator.clipboard?.writeText(@js($code));
                this.copied = true;
                setTimeout(() => { this.copied = false }, 2000);
            },
        }"
        x-show="open"
        x-cloak
        x-transition.opacity.duration.300ms
        class="coupon-popup-overlay"
        x-on:keydown.escape.window="close()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="coupon-popup-title"
    >
        <div class="coupon-popup-backdrop" x-on:click="close()"></div>

        <div
            class="coupon-popup"
            x-show="open"
            x-transition:enter="coupon-popup-enter"
            x-transition:enter-start="coupon-popup-enter-start"
            x-transition:enter-end="coupon-popup-enter-end"
        >
            <button type="button" class="coupon-popup-close" x-on:click="close()"
                    aria-label="{{ $arabic ? 'إغلاق' : 'Close' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>

            @if ($image)
                <div class="coupon-popup-image">
                    <img src="{{ $image }}" alt="{{ $title }}" loading="lazy">
                </div>
            @endif

            <div class="coupon-popup-body">
                <span class="coupon-popup-ribbon">
                    {{ $arabic ? 'عرض خاص' : 'Special offer' }}
                </span>

                @if (filled($title))
                    <h2 id="coupon-popup-title">{{ $title }}</h2>
                @endif

                @if (filled($text))
                    <p>{{ $text }}</p>
                @endif

                @if (filled($code))
                    <button type="button" class="coupon-popup-code" x-on:click="copy()">
                        <span class="coupon-popup-code-value" dir="ltr">{{ $code }}</span>

                        <span class="coupon-popup-code-hint" x-show="!copied">
                            {{ $arabic ? 'اضغطي للنسخ' : 'Tap to copy' }}
                        </span>

                        <span class="coupon-popup-code-hint is-copied" x-show="copied" x-cloak>
                            {{ $arabic ? 'تم النسخ ✓' : 'Copied ✓' }}
                        </span>
                    </button>
                @endif

                <a href="{{ route('site.shop') }}" class="coupon-popup-cta" x-on:click="close()">
                    {{ filled($buttonLabel) ? $buttonLabel : ($arabic ? 'تسوقي الآن' : 'Shop now') }}
                </a>

                <button type="button" class="coupon-popup-dismiss" x-on:click="close()">
                    {{ $arabic ? 'لا شكرًا' : 'No thanks' }}
                </button>
            </div>
        </div>
    </div>
@endif
