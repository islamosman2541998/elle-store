@props([
    'event' => null,
    'payload' => [],
    'eventId' => null,
])

@php
    use App\Models\StoreSetting;

    $settings = StoreSetting::current();
    $eventId = $eventId ?: 'event_' . \Illuminate\Support\Str::uuid()->toString();
@endphp

@if ($settings->tracking_enabled && $event)
    <script>
        window.dataLayer = window.dataLayer || [];

        window.dataLayer.push({
            event: '{{ $event }}',
            event_id: '{{ $eventId }}',
            ecommerce: @json($payload),
        });

        @if ($settings->meta_pixel_id)
            if (typeof fbq === 'function') {
                fbq('track', '{{ $event }}', @json($payload), {
                    eventID: '{{ $eventId }}'
                });
            }
        @endif

        @if ($settings->tiktok_pixel_id)
            if (typeof ttq !== 'undefined') {
                ttq.track('{{ $event }}', @json($payload));
            }
        @endif

        @if ($settings->snapchat_pixel_id)
            if (typeof snaptr === 'function') {
                snaptr('track', '{{ strtoupper($event) }}', @json($payload));
            }
        @endif

        @if ($settings->pinterest_tag_id)
            if (typeof pintrk === 'function') {
                pintrk('track', '{{ $event }}', @json($payload));
            }
        @endif
    </script>
@endif