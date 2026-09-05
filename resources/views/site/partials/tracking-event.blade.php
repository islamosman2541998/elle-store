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
    {{-- Fires once the pixels have loaded, through the shared bridge. --}}
    <script>
        (function () {
            var fire = function () {
                if (typeof window.elleTrack === 'function') {
                    window.elleTrack(@js($event), @js($payload), @js($eventId));
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fire);
            } else {
                fire();
            }
        })();
    </script>
@endif
