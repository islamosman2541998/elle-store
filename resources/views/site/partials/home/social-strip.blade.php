@php
    $enabled = $homepage['social_strip']['enabled'] ?? false;
    $isArabic = app()->getLocale() === 'ar';

    // socialLinks comes from the storefront view composer.
    $links = collect($socialLinks ?? [])->filter(fn ($link) => filled($link->url));
@endphp

@if ($enabled && $links->count())
    <section class="home-social-section">
        <div class="site-container">
            <div class="home-social-card">
                <div>
                    <h2 class="home-social-title">
                        {{ $isArabic ? 'تابعينا على السوشيال' : 'Follow us' }}
                    </h2>

                    <p class="home-social-text">
                        {{ $isArabic
                            ? 'كل جديد وعروضنا أول بأول — وشوفي القطع على الطبيعة.'
                            : 'New arrivals and offers first, and see the pieces in real life.' }}
                    </p>
                </div>

                <div class="home-social-links">
                    @foreach ($links as $link)
                        @php
                            $platform = strtolower((string) ($link->platform ?? ''));

                            $label = $link->label
                                ?? ($isArabic ? $link->label_ar : $link->label_en)
                                ?? ucfirst($platform);
                        @endphp

                        <a
                            href="{{ $link->url }}"
                            target="_blank"
                            rel="noopener"
                            class="home-social-link"
                            title="{{ $label }}"
                        >
                            @if (str_contains($platform, 'facebook'))
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5h1.7V4.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V11H7.3v3h2.8v8h3.4Z" />
                                </svg>
                            @elseif (str_contains($platform, 'instagram'))
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="4" y="4" width="16" height="16" rx="5" />
                                    <circle cx="12" cy="12" r="3.5" />
                                    <circle cx="17" cy="7" r="1" />
                                </svg>
                            @elseif (str_contains($platform, 'whatsapp'))
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.5A9.4 9.4 0 0 0 4 16.8L3 21l4.3-1.1A9.4 9.4 0 1 0 12 2.5Zm0 17a7.4 7.4 0 0 1-3.8-1l-.3-.2-2.5.7.7-2.4-.2-.3A7.5 7.5 0 1 1 12 19.5Zm4.1-5.6c-.2-.1-1.3-.6-1.5-.7-.2-.1-.4-.1-.6.1-.2.3-.7.8-.8 1-.2.2-.3.2-.6.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.3 0-.4.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1s1 2.5 1.1 2.7c.1.2 1.9 3 4.7 4.1.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.3-.5 1.5-1 .2-.5.2-.9.1-1 0-.1-.2-.2-.5-.3Z" />
                                </svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M8 12h8M12 8v8" />
                                </svg>
                            @endif

                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
