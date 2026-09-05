<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    @yield('title', $storeSettings->store_name ?? config('app.name'))
</title>

@if (!empty($storeSettings->meta_description))
    <meta name="description" content="{{ $storeSettings->meta_description }}">
@endif

@if (!empty($storeSettings->meta_keywords))
    <meta name="keywords" content="{{ $storeSettings->meta_keywords }}">
@endif

@if (!empty($storeSettings->canonical_url))
    <link rel="canonical" href="{{ $storeSettings->canonical_url }}">
@endif

@if (!empty($storeSettings->favicon))
    <link rel="icon" href="{{ asset('storage/' . $storeSettings->favicon) }}">
@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

@if (app()->getLocale() === 'ar')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@else
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endif

<link rel="stylesheet" href="{{ asset('site/css/style.css') }}">

@livewireStyles

@stack('styles')