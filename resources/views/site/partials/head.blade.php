@php
    use App\Services\SeoService;

    $seo = app(SeoService::class);
    $locale = app()->getLocale();
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>@yield('title', $seo->title())</title>

@if ($seo->get('description'))
    <meta name="description" content="{{ $seo->get('description') }}">
@endif

@if ($seo->get('keywords'))
    <meta name="keywords" content="{{ $seo->get('keywords') }}">
@endif

{{-- Indexing policy, chosen in the store settings. --}}
<meta name="robots" content="{{ $seo->get('robots') }}">

{{-- The canonical is this page's own URL: a single site-wide canonical would
     tell search engines every page is a duplicate of one. --}}
<link rel="canonical" href="{{ $seo->get('canonical') }}">

{{-- Open Graph: what Facebook, WhatsApp and Instagram show when a link is shared. --}}
<meta property="og:site_name" content="{{ $seo->get('site_name') }}">
<meta property="og:type" content="{{ $seo->get('type') }}">
<meta property="og:title" content="{{ $seo->title() }}">
<meta property="og:url" content="{{ $seo->get('canonical') }}">
<meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">

@if ($seo->get('description'))
    <meta property="og:description" content="{{ $seo->get('description') }}">
@endif

@if ($seo->get('image'))
    <meta property="og:image" content="{{ $seo->get('image') }}">
    <meta property="og:image:alt" content="{{ $seo->title() }}">
@endif

<meta name="twitter:card" content="{{ $seo->get('image') ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo->title() }}">

@if ($seo->get('description'))
    <meta name="twitter:description" content="{{ $seo->get('description') }}">
@endif

@if ($seo->get('image'))
    <meta name="twitter:image" content="{{ $seo->get('image') }}">
@endif

@if (!empty($storeSettings->google_site_verification))
    <meta name="google-site-verification" content="{{ $storeSettings->google_site_verification }}">
@endif

@if (!empty($storeSettings->bing_site_verification))
    <meta name="msvalidate.01" content="{{ $storeSettings->bing_site_verification }}">
@endif

@if (!empty($storeSettings->favicon))
    <link rel="icon" href="{{ asset('storage/' . $storeSettings->favicon) }}">
@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

@if ($locale === 'ar')
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@else
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endif

<link rel="stylesheet" href="{{ asset('site/css/style.css') }}">

{{-- Structured data: who the shop is, plus whatever this page described. --}}
@foreach ($seo->structuredData() as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach

@include('site.partials.tracking-scripts')

@livewireStyles

@stack('styles')
