@php
    use App\Models\StoreSetting;

    $settings = StoreSetting::current();
@endphp

@if ($settings->tracking_enabled && $settings->google_tag_manager_id)
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $settings->google_tag_manager_id }}"
                height="0"
                width="0"
                style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif