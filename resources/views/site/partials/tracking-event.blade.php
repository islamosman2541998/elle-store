@php
    use App\Models\StoreSetting;

    /*
     * Included, not rendered as a component: @props would try to read an
     * $attributes bag, and on pages that already have an $attributes variable
     * of their own (product attributes) it blew up. Plain defaults instead.
     */
    $settings = StoreSetting::current();

    $event = $event ?? null;
    $payload = $payload ?? [];
    $eventId = $eventId ?? ('event_' . \Illuminate\Support\Str::uuid()->toString());
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
