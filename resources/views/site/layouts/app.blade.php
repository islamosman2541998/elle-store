<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('site.partials.head')
</head>

<body class="min-h-screen bg-soft text-dark">
    @include('site.partials.tracking-noscript')

    <div class="flex min-h-screen flex-col">
        @include('site.partials.header')

        <main class="flex-1">
            @yield('content')
        </main>

        @include('site.partials.footer')
    </div>

    <div
        id="site-toast"
        class="site-toast"
        role="alert"
        aria-live="polite"
    >
        <div class="site-toast-icon" data-toast-icon>
            ✓
        </div>

        <div>
            <div class="site-toast-title" data-toast-title>
                {{ app()->getLocale() === 'ar' ? 'تم بنجاح' : 'Success' }}
            </div>

            <div class="site-toast-message" data-toast-message>
                {{ app()->getLocale() === 'ar' ? 'تم تنفيذ العملية بنجاح' : 'Action completed successfully' }}
            </div>
        </div>
    </div>

    {{-- Single shared quick-view dialog, opened by product cards. --}}
    @livewire('site.product-quick-view')

    @include('site.partials.whatsapp-button')
    @include('site.partials.coupon-popup')

    @include('site.partials.scripts')
</body>
</html>