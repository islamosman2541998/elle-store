@php
    $footerSetting = $footerSettings ?? \App\Models\FooterSetting::query()->where('is_active', true)->first();

    $footerLinksCollection =
        $footerLinks ?? \App\Models\FooterLink::query()->where('is_active', true)->orderBy('sort_order')->get();

    $footerGroups = $footerLinksCollection->groupBy('group');

    $socialLinksCollection =
        $socialLinks ?? \App\Models\SocialLink::query()->where('is_active', true)->orderBy('sort_order')->get();

    $paymentMethodsCollection = $paymentMethods ?? collect();

    if ($paymentMethodsCollection->isEmpty()) {
        $paymentModelClasses = [
            \App\Models\PaymentMethodDisplay::class,
            \App\Models\PaymentMethod::class,
            \App\Models\DisplayedPaymentMethod::class,
            \App\Models\PaymentDisplayMethod::class,
            \App\Models\PaymentOption::class,
        ];

        foreach ($paymentModelClasses as $paymentModelClass) {
            if (class_exists($paymentModelClass)) {
                $paymentMethodsCollection = $paymentModelClass
                    ::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                if ($paymentMethodsCollection->count()) {
                    break;
                }
            }
        }
    }

    $showPaymentMethods = (bool) ($footerSetting?->show_payment_methods ?? true);

    $footerTitle = $footerSetting?->title ?? ($storeSettings->store_name ?? config('app.name'));

    $footerDescription =
        $footerSetting?->description ??
        (app()->getLocale() === 'ar'
            ? 'متجر إلكتروني يوفر لك تجربة تسوق سهلة وسريعة مع أفضل المنتجات والعروض.'
            : 'An online store that gives you an easy shopping experience with top products and offers.');

    $copyright =
        $footerSetting?->copyright ?? (app()->getLocale() === 'ar' ? 'جميع الحقوق محفوظة' : 'All rights reserved');

    $footerGroupTitle = function ($group) {
        $groupKey = strtolower(trim((string) $group));

        return match ($groupKey) {
            'main' => app()->getLocale() === 'ar' ? 'روابط رئيسية' : 'Main Links',
            'quick_links' => app()->getLocale() === 'ar' ? 'روابط سريعة' : 'Quick Links',
            'customer_service' => app()->getLocale() === 'ar' ? 'خدمة العملاء' : 'Customer Service',
            'policies' => app()->getLocale() === 'ar' ? 'السياسات' : 'Policies',
            'categories' => app()->getLocale() === 'ar' ? 'الأقسام' : 'Categories',
            default => str_replace('_', ' ', $group),
        };
    };

    $socialIconSvg = function ($platform) {
        $platform = strtolower(trim((string) $platform));

        if (str_contains($platform, 'facebook')) {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5h1.7V4.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V11H7.3v3h2.8v8h3.4Z"/></svg>';
        }

        if (str_contains($platform, 'instagram')) {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r="1"/></svg>';
        }

        if (str_contains($platform, 'tiktok')) {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.7 3c.3 2.4 1.7 3.8 4 4v3.2c-1.5.1-2.8-.4-4-1.2v6.1c0 4.4-4.8 7.1-8.5 4.8-3.6-2.2-3.4-7.8.4-9.7 1.1-.5 2.2-.6 3.4-.4v3.4c-.4-.1-.8-.2-1.2-.1-2.3.2-3.1 3.3-1.1 4.5 1.4.9 3.6.1 3.6-2.1V3h3.4Z"/></svg>';
        }

        if (str_contains($platform, 'youtube')) {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.6 7.2s-.2-1.5-.8-2.1c-.8-.8-1.7-.8-2.1-.9C15.8 4 12 4 12 4s-3.8 0-6.7.2c-.4.1-1.3.1-2.1.9-.6.6-.8 2.1-.8 2.1S2.2 9 2.2 10.8v1.7c0 1.8.2 3.6.2 3.6s.2 1.5.8 2.1c.8.8 1.8.8 2.3.9 1.7.2 6.5.2 6.5.2s3.8 0 6.7-.2c.4-.1 1.3-.1 2.1-.9.6-.6.8-2.1.8-2.1s.2-1.8.2-3.6v-1.7c0-1.8-.2-3.6-.2-3.6ZM10.2 14.6V8.9l5 2.9-5 2.8Z"/></svg>';
        }

        if (str_contains($platform, 'twitter') || $platform === 'x') {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.2 3h3.1l-6.8 7.8 8 10.2h-6.3l-4.9-6.3L5.7 21H2.6l7.3-8.4L2.2 3h6.4l4.4 5.7L18.2 3Zm-1.1 16.2h1.7L7.7 4.7H5.9l11.2 14.5Z"/></svg>';
        }

        if (str_contains($platform, 'snap')) {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5c3 0 5.2 2.2 5.2 5.3v2.5c0 .4.2.7.6.9l1.4.7c.5.3.5 1-.1 1.2l-1.7.6c-.4.1-.6.5-.5.9.4 1.8 1.8 2.4 2.8 2.8.4.2.5.8.1 1-1 .6-2.2.7-3.2.6-.5 1-1.4 1.8-2.8 1.8-.8 0-1.2-.3-1.8-.3s-1 .3-1.8.3c-1.4 0-2.3-.8-2.8-1.8-1 .1-2.2 0-3.2-.6-.4-.2-.3-.8.1-1 1-.4 2.4-1 2.8-2.8.1-.4-.1-.8-.5-.9l-1.7-.6c-.6-.2-.6-.9-.1-1.2l1.4-.7c.4-.2.6-.5.6-.9V7.8c0-3.1 2.2-5.3 5.2-5.3Z"/></svg>';
        }

        if (str_contains($platform, 'linkedin')) {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 8.8H3.4V21h3.1V8.8ZM5 3C4 3 3.2 3.8 3.2 4.8S4 6.6 5 6.6s1.8-.8 1.8-1.8S6 3 5 3ZM21 14.2c0-3.3-1.8-5.5-4.7-5.5-1.6 0-2.7.9-3.2 1.7V8.8H10V21h3.1v-6.5c0-1.7.8-2.8 2.4-2.8s2.4 1.1 2.4 2.9V21H21v-6.8Z"/></svg>';
        }

        if (str_contains($platform, 'whatsapp')) {
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5A9.4 9.4 0 0 0 4 16.8L3 21l4.3-1.1A9.4 9.4 0 1 0 12 2.5Zm0 17a7.4 7.4 0 0 1-3.8-1l-.3-.2-2.5.7.7-2.4-.2-.3A7.5 7.5 0 1 1 12 19.5Zm4.1-5.6c-.2-.1-1.3-.6-1.5-.7-.2-.1-.4-.1-.6.1-.2.3-.7.8-.8 1-.2.2-.3.2-.6.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.3 0-.4.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1s1 2.5 1.1 2.7c.1.2 1.9 3 4.7 4.1.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.3-.5 1.5-1 .2-.5.2-.9.1-1 0-.1-.2-.2-.5-.3Z"/></svg>';
        }

        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg>';
    };
@endphp

<footer class="site-footer">
    <div class="site-container">
        <div class="footer-main">
            <div class="footer-brand">
                <a href="{{ route('site.home') }}" class="footer-logo-link">
                    @if ($footerSetting?->logo)
                        <img src="{{ asset('storage/' . $footerSetting->logo) }}" alt="{{ $footerTitle }}"
                            class="footer-logo">
                    @elseif (!empty($storeSettings->logo))
                        <img src="{{ asset('storage/' . $storeSettings->logo) }}" alt="{{ $footerTitle }}"
                            class="footer-logo">
                    @else
                        <span class="footer-logo-text">
                            {{ $footerTitle }}
                        </span>
                    @endif
                </a>

                <h3>
                    {{ $footerTitle }}
                </h3>

                <p>
                    {{ $footerDescription }}
                </p>

                @if ($footerSetting?->show_social_links && $socialLinksCollection->count())
                    <div class="footer-socials">
                        @foreach ($socialLinksCollection as $social)
                            @php
                                $platformForIcon = $social->platform ?? ($social->icon ?? $social->label);
                                $socialLabel =
                                    $social->label ?? ($social->label_ar ?? ($social->label_en ?? $platformForIcon));
                            @endphp

                            <a href="{{ $social->url }}"
                                @if ($social->open_in_new_tab) target="_blank" rel="noopener" @endif
                                class="footer-social-link" aria-label="{{ $socialLabel }}"
                                title="{{ $socialLabel }}">
                                {!! $socialIconSvg($platformForIcon) !!}
                            </a>
                        @endforeach
                    </div>
                @endif

            </div>

            {{-- Link columns and the newsletter are direct children of
                 .footer-main so the whole footer stays on one row. --}}
            @forelse ($footerGroups as $group => $links)
                <div class="footer-links-column">
                    <h4>
                        {{ $footerGroupTitle($group) }}
                    </h4>

                    <ul>
                        @foreach ($links as $link)
                            <li>
                                <a href="{{ $link->resolved_url }}"
                                    @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif>
                                    {{ $link->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="footer-links-column">
                    <h4>
                        {{ app()->getLocale() === 'ar' ? 'روابط مهمة' : 'Important Links' }}
                    </h4>

                    <ul>
                        <li>
                            <a href="{{ route('site.home') }}">
                                {{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}
                            </a>
                        </li>
                    </ul>
                </div>
            @endforelse

            @if ($footerSetting?->show_newsletter)
                <div class="footer-links-column footer-newsletter-column">
                    <h4>
                        {{ app()->getLocale() === 'ar' ? 'النشرة البريدية' : 'Newsletter' }}
                    </h4>

                    @if ($footerSetting->newsletter_description)
                        <p>
                            {{ $footerSetting->newsletter_description }}
                        </p>
                    @endif

                    <form class="footer-mini-newsletter-form" action="#" method="POST">
                        <input type="email" name="email"
                            placeholder="{{ app()->getLocale() === 'ar' ? 'بريدك الإلكتروني' : 'Your email' }}"
                            class="footer-mini-newsletter-input">

                        <button type="submit" class="footer-mini-newsletter-btn">
                            {{ app()->getLocale() === 'ar' ? 'اشترك' : 'Subscribe' }}
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="footer-bottom">
            <div>
                ©  {{ $copyright }}
            </div>

           @if ($showPaymentMethods && $paymentMethodsCollection->count())
    <div class="footer-payments">
        @foreach ($paymentMethodsCollection as $paymentMethod)
            @php
                $paymentName =
                    $paymentMethod->name ??
                    (app()->getLocale() === 'ar'
                        ? ($paymentMethod->name_ar ?? $paymentMethod->name_en ?? null)
                        : ($paymentMethod->name_en ?? $paymentMethod->name_ar ?? null))
                    ?? $paymentMethod->key
                    ?? 'Payment';

                $paymentImage = $paymentMethod->image ?? $paymentMethod->icon ?? null;

                $paymentImageUrl = $paymentImage
                    ? (\Illuminate\Support\Str::startsWith($paymentImage, ['http://', 'https://'])
                        ? $paymentImage
                        : asset('storage/' . $paymentImage))
                    : null;
            @endphp

            <span class="footer-payment-item" title="{{ $paymentName }}">
                @if ($paymentImageUrl)
                    <img
                        src="{{ $paymentImageUrl }}"
                        alt="{{ $paymentName }}"
                    >
                @else
                    {{ $paymentName }}
                @endif
            </span>
        @endforeach
    </div>
@endif
        </div>
    </div>
</footer>
