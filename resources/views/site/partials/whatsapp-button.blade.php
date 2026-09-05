@php
    $arabic = app()->getLocale() === 'ar';

    $number = preg_replace('/[^0-9]/', '', (string) $storeSettings->whatsapp);

    // wa.me wants the country code with no plus: 01… becomes 201…
    if (str_starts_with($number, '00')) {
        $number = substr($number, 2);
    } elseif (str_starts_with($number, '01')) {
        $number = '2' . $number;
    }

    $message = $arabic
        ? $storeSettings->whatsapp_button_message_ar
        : $storeSettings->whatsapp_button_message_en;

    $href = 'https://wa.me/' . $number
        . (filled($message) ? '?text=' . rawurlencode($message) : '');
@endphp

@if ($storeSettings->whatsapp_button_enabled && filled($number))
    {{-- Pinned to the page's trailing edge, so it sits on the left in Arabic
         and on the right in English without a second rule. --}}
    <a
        href="{{ $href }}"
        target="_blank"
        rel="noopener noreferrer"
        class="whatsapp-fab"
        aria-label="{{ $arabic ? 'تواصلي معنا على واتساب' : 'Chat with us on WhatsApp' }}"
    >
        <span class="whatsapp-fab-pulse" aria-hidden="true"></span>

        <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
            <path d="M16.04 3.2c-7.1 0-12.87 5.77-12.87 12.87 0 2.27.6 4.49 1.73 6.44L3.2 28.8l6.45-1.69a12.83 12.83 0 0 0 6.39 1.63h.01c7.09 0 12.86-5.77 12.86-12.87 0-3.44-1.34-6.67-3.77-9.1a12.78 12.78 0 0 0-9.1-3.77Zm0 23.35h-.01a10.7 10.7 0 0 1-5.44-1.49l-.39-.23-4.04 1.06 1.08-3.94-.25-.4a10.66 10.66 0 0 1-1.64-5.68c0-5.9 4.8-10.7 10.7-10.7 2.86 0 5.54 1.11 7.56 3.14a10.62 10.62 0 0 1 3.13 7.57c0 5.9-4.8 10.67-10.7 10.67Zm5.87-7.99c-.32-.16-1.9-.94-2.2-1.05-.29-.11-.5-.16-.72.16-.21.32-.82 1.05-1.01 1.26-.19.22-.37.24-.69.08-.32-.16-1.36-.5-2.59-1.6-.96-.85-1.6-1.91-1.79-2.23-.19-.32-.02-.5.14-.66.14-.14.32-.37.48-.56.16-.19.21-.32.32-.53.11-.22.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.38-.26-.62-.52-.54-.72-.55l-.61-.01c-.21 0-.56.08-.85.4-.29.32-1.11 1.09-1.11 2.65s1.14 3.08 1.3 3.29c.16.21 2.24 3.42 5.43 4.8.76.33 1.35.52 1.81.67.76.24 1.45.21 2 .13.61-.09 1.9-.78 2.16-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
        </svg>
    </a>
@endif
