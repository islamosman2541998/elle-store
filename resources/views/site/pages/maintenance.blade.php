@php
    $isArabic = app()->getLocale() === 'ar';

    $storeName = $storeSettings->store_name ?? config('app.name');

    $message = $storeSettings->maintenance_message
        ?? ($isArabic
            ? 'المتجر تحت الصيانة حاليًا، نرجع لك قريبًا.'
            : 'The store is under maintenance. We will be back shortly.');
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $storeName }}</title>

    @if (!empty($storeSettings->favicon))
        <link rel="icon" href="{{ asset('storage/' . $storeSettings->favicon) }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('site/css/style.css') }}">
</head>

<body class="min-h-screen bg-dark text-white">
    <main class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
        @if (!empty($storeSettings->logo))
            <img
                src="{{ asset('storage/' . $storeSettings->logo) }}"
                alt="{{ $storeName }}"
                class="mb-8 h-16 w-auto max-w-52 object-contain"
                style="filter: brightness(0) invert(1);"
            >
        @else
            <h1 class="mb-8 text-3xl font-black">{{ $storeName }}</h1>
        @endif

        <h2 class="text-xl font-black md:text-2xl">
            {{ $isArabic ? 'المتجر مغلق مؤقتًا' : 'The store is temporarily closed' }}
        </h2>

        <p class="mt-4 max-w-md text-sm leading-7 text-white/60 md:text-base">
            {{ $message }}
        </p>

        @if (!empty($storeSettings->phone) || !empty($storeSettings->whatsapp))
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                @if (!empty($storeSettings->whatsapp))
                    <a
                        href="https://wa.me/{{ $storeSettings->whatsapp }}"
                        target="_blank"
                        rel="noopener"
                        class="rounded-full bg-brand px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-dark"
                    >
                        {{ $isArabic ? 'تواصلي معنا على واتساب' : 'Contact us on WhatsApp' }}
                    </a>
                @endif

                @if (!empty($storeSettings->phone))
                    <a
                        href="tel:{{ $storeSettings->phone }}"
                        class="rounded-full border border-white/20 px-6 py-3 text-sm font-bold text-white transition hover:border-brand hover:text-brand"
                    >
                        {{ $storeSettings->phone }}
                    </a>
                @endif
            </div>
        @endif
    </main>
</body>

</html>
