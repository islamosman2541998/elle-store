@php
    $wishlistUrl = '#';
    $loginUrl = '#';
    $accountUrl = '#';
    $ordersUrl = '#';
    $logoutUrl = '#';

    if (\Illuminate\Support\Facades\Route::has('site.wishlist.index')) {
        $wishlistUrl = route('site.wishlist.index');
    } elseif (\Illuminate\Support\Facades\Route::has('site.wishlist')) {
        $wishlistUrl = route('site.wishlist');
    } elseif (\Illuminate\Support\Facades\Route::has('wishlist.index')) {
        $wishlistUrl = route('wishlist.index');
    }

    if (\Illuminate\Support\Facades\Route::has('site.customer.login')) {
        $loginUrl = route('site.customer.login');
    } elseif (\Illuminate\Support\Facades\Route::has('site.login')) {
        $loginUrl = route('site.login');
    } elseif (\Illuminate\Support\Facades\Route::has('customer.login')) {
        $loginUrl = route('customer.login');
    } elseif (\Illuminate\Support\Facades\Route::has('login')) {
        $loginUrl = route('login');
    }

    if (\Illuminate\Support\Facades\Route::has('site.customer.account')) {
        $accountUrl = route('site.customer.account');
    } elseif (\Illuminate\Support\Facades\Route::has('site.account')) {
        $accountUrl = route('site.account');
    } elseif (\Illuminate\Support\Facades\Route::has('customer.account')) {
        $accountUrl = route('customer.account');
    } elseif (\Illuminate\Support\Facades\Route::has('profile.edit')) {
        $accountUrl = route('profile.edit');
    }

    if (\Illuminate\Support\Facades\Route::has('site.customer.orders')) {
        $ordersUrl = route('site.customer.orders');
    }

    if (\Illuminate\Support\Facades\Route::has('site.customer.logout')) {
        $logoutUrl = route('site.customer.logout');
    }

    $customerIsLoggedIn = false;
    $customer = null;

    try {
        $customerIsLoggedIn = auth('customer')->check();
        $customer = auth('customer')->user();
    } catch (\Throwable $e) {
        $customerIsLoggedIn = false;
        $customer = null;
    }

    $customerName = $customer?->name ?: (app()->getLocale() === 'ar' ? 'حسابي' : 'Account');

    $authUrl = $customerIsLoggedIn ? $accountUrl : $loginUrl;

    $authLabel = $customerIsLoggedIn ? $customerName : (app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Login');

    $customerInitial = mb_substr($customerName, 0, 1);

$isHomePage = app('view')->hasSection('transparent_header');
@endphp

<header
    data-site-header
    data-header-transparent="{{ $isHomePage ? 'true' : 'false' }}"
    class="site-header {{ $isHomePage ? 'site-header-transparent' : 'site-header-solid is-scrolled' }} fixed left-0 top-0 z-50 w-full transition"
>
    @if ($storeSettings->announcement_bar_enabled && $storeSettings->announcement_bar_text)
        <div class="overflow-hidden border-b border-white/10"
            style="
                background-color: {{ $storeSettings->announcement_bar_bg_color ?: '#111827' }};
                color: {{ $storeSettings->announcement_bar_text_color ?: '#ffffff' }};
            ">
            <div class="relative flex min-h-10 items-center">
                @php
                    $announcementContent = $storeSettings->announcement_bar_text;
                    $announcementSpeed = $storeSettings->announcement_bar_speed ?: 25;
                @endphp

                @if ($storeSettings->announcement_bar_url)
                    <a href="{{ $storeSettings->announcement_bar_url }}"
                        @if ($storeSettings->announcement_bar_open_in_new_tab) target="_blank" rel="noopener" @endif
                        class="announcement-track hover:opacity-90"
                        style="animation-duration: {{ $announcementSpeed }}s;">
                        <span>{{ $announcementContent }}</span>
                        <span>{{ $announcementContent }}</span>
                        <span>{{ $announcementContent }}</span>
                        <span>{{ $announcementContent }}</span>
                    </a>
                @else
                    <div class="announcement-track" style="animation-duration: {{ $announcementSpeed }}s;">
                        <span>{{ $announcementContent }}</span>
                        <span>{{ $announcementContent }}</span>
                        <span>{{ $announcementContent }}</span>
                        <span>{{ $announcementContent }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="site-container">
        <div class="site-navbar flex min-h-20 items-center justify-between gap-4">
            <a href="{{ route('site.home') }}" class="flex shrink-0 items-center gap-3">
                @if (!empty($storeSettings->logo))
                    <img src="{{ asset('storage/' . $storeSettings->logo) }}" alt="{{ $storeSettings->store_name }}"
                        class="h-12 w-auto max-w-36 object-contain">
                @endif
            </a>

            <nav class="hidden items-center gap-7 lg:flex">
                @foreach ($navigation['header_links'] ?? collect() as $link)
                    <a href="{{ $link->resolved_url }}"
                        @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif
                        class="site-nav-link text-sm font-semibold">
                        {{ $link->title }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="#" class="site-header-icon"
                    aria-label="{{ app()->getLocale() === 'ar' ? 'بحث' : 'Search' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6Z" />
                    </svg>
                </a>

             @livewire('site.wishlist-counter')

                @livewire('site.cart-counter')

                @if (!$customerIsLoggedIn)
                    <a href="{{ $loginUrl }}" class="site-header-icon hidden lg:inline-flex"
                        aria-label="{{ $authLabel }}" title="{{ $authLabel }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0 1 15 0" />
                        </svg>
                    </a>
                @else
                    <details class="site-account-dropdown hidden lg:block">
                        <summary class="site-account-trigger">
                            <span class="site-account-avatar">
                                {{ $customerInitial }}
                            </span>

                            <span class="site-account-name">
                                {{ \Illuminate\Support\Str::limit($customerName, 16) }}
                            </span>

                            <svg class="site-account-chevron" fill="none" stroke="currentColor" stroke-width="2.3"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>

                        <div class="site-account-menu">
                            <a href="{{ $accountUrl }}">
                                {{ app()->getLocale() === 'ar' ? 'حسابي' : 'My Account' }}
                            </a>

                            <a href="{{ $ordersUrl }}">
                                {{ app()->getLocale() === 'ar' ? 'طلباتي' : 'My Orders' }}
                            </a>

                            <form method="POST" action="{{ $logoutUrl }}">
                                @csrf

                                <button type="submit">
                                    {{ app()->getLocale() === 'ar' ? 'تسجيل خروج' : 'Logout' }}
                                </button>
                            </form>
                        </div>
                    </details>
                @endif

                <a href="{{ route('switch.language', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar']) }}"
                    class="site-header-lang hidden sm:inline-flex">
                    {{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}
                </a>

                <button type="button" data-mobile-menu-button class="site-header-icon site-mobile-menu-btn lg:hidden"
                    aria-label="Menu">
                    <svg class="site-mobile-menu-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div data-mobile-menu class="hidden border-t border-white/20 bg-black/35 py-4 backdrop-blur-md lg:hidden">
            <nav class="flex flex-col gap-3">
                @foreach ($navigation['mobile_links'] ?? ($navigation['header_links'] ?? collect()) as $link)
                    <a href="{{ $link->resolved_url }}"
                        @if ($link->open_in_new_tab) target="_blank" rel="noopener" @endif
                        class="rounded-xl px-4 py-3 text-sm font-bold text-white hover:bg-white/10 hover:text-white">
                        {{ $link->title }}
                    </a>
                @endforeach

                <div class="mt-2 border-t border-white/10 pt-3">
                    <a href="{{ $wishlistUrl }}" class="mobile-menu-action">
                        <span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 21s-7.2-4.35-9.6-8.7C.35 8.6 2.55 4 6.75 4c2.05 0 3.45 1.05 4.25 2.05C11.8 5.05 13.2 4 15.25 4c4.2 0 6.4 4.6 4.35 8.3C19.2 16.65 12 21 12 21Z" />
                            </svg>
                        </span>

                        {{ app()->getLocale() === 'ar' ? 'قائمة الرغبات' : 'Wishlist' }}
                    </a>

                    @if (!$customerIsLoggedIn)
                        <a href="{{ $loginUrl }}" class="mobile-menu-action">
                            <span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.5 20.25a7.5 7.5 0 0 1 15 0" />
                                </svg>
                            </span>

                            {{ app()->getLocale() === 'ar' ? 'تسجيل الدخول' : 'Login' }}
                        </a>
                    @else
                        <a href="{{ $accountUrl }}" class="mobile-menu-action">
                            <span class="mobile-menu-avatar">
                                {{ $customerInitial }}
                            </span>

                            {{ $customerName }}
                        </a>

                        <a href="{{ $ordersUrl }}" class="mobile-menu-action">
                            <span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6M7 3h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V5a2 2 0 0 1 2-2Z" />
                                </svg>
                            </span>

                            {{ app()->getLocale() === 'ar' ? 'طلباتي' : 'My Orders' }}
                        </a>

                        <form method="POST" action="{{ $logoutUrl }}">
                            @csrf

                            <button type="submit" class="mobile-menu-action mobile-menu-logout">
                                <span>
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M18 12H9m9 0-3-3m3 3-3 3" />
                                    </svg>
                                </span>

                                {{ app()->getLocale() === 'ar' ? 'تسجيل خروج' : 'Logout' }}
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('switch.language', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar']) }}"
                        class="mobile-menu-action sm:hidden">
                        <span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.6 9h16.8M3.6 15h16.8M12 3c2.2 2.2 3.2 5.2 3.2 9S14.2 18.8 12 21c-2.2-2.2-3.2-5.2-3.2-9S9.8 5.2 12 3Z" />
                            </svg>
                        </span>

                        {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                    </a>
                </div>
            </nav>
        </div>
    </div>
</header>
