@php
    $locale = app()->getLocale();
    $storeName = $storeSettings->store_name ?: config('app.name');
@endphp
{{ $storeName }}
{{ str_repeat('=', mb_strlen($storeName)) }}

{{ $subjectLine }}

{{ $bodyText }}
@if ($actionUrl)

{{ $actionLabel ?: ($locale === 'ar' ? 'عرض التفاصيل' : 'View details') }}:
{{ $actionUrl }}
@endif

--
{{ $locale === 'ar'
    ? 'هذه رسالة تلقائية من المتجر، برجاء عدم الرد عليها.'
    : 'This is an automated message from the store. Please do not reply.' }}
@if ($storeSettings->phone)
{{ $locale === 'ar' ? 'هاتف' : 'Phone' }}: {{ $storeSettings->phone }}
@endif
@if ($storeSettings->whatsapp)
WhatsApp: {{ $storeSettings->whatsapp }}
@endif
