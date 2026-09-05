<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shipping carrier integrations
    |--------------------------------------------------------------------------
    |
    | Endpoints and field names live here rather than in the driver so they can
    | be corrected without touching code when a carrier changes its API.
    |
    | Per-account values (API key, base URL, webhook secret, pickup location)
    | are stored on each shipping company row in the dashboard, not here.
    |
    */

    'timeout' => 20,

    'bosta' => [

        // Bosta publishes a live and a staging host. Which one is used is
        // decided per company by the "sandbox mode" toggle, unless that
        // company has an explicit base URL set.
        'base_url' => env('BOSTA_BASE_URL', 'https://app.bosta.co/api/v2'),
        'sandbox_url' => env('BOSTA_SANDBOX_URL', 'https://stg-app.bosta.co/api/v2'),

        // Bosta authenticates with the raw API key in the Authorization
        // header (no "Bearer" prefix). Set a prefix here if that changes.
        'auth_header' => env('BOSTA_AUTH_HEADER', 'Authorization'),
        'auth_prefix' => env('BOSTA_AUTH_PREFIX', ''),

        'endpoints' => [
            'create_delivery' => 'deliveries',
            // {reference} is the carrier shipment id, or the AWB as a fallback.
            'track_delivery' => 'deliveries/{reference}',
            'cancel_delivery' => 'deliveries/{reference}',
        ],

        // 10 = a normal forward delivery ("Send") in Bosta's type list.
        'delivery_type' => (int) env('BOSTA_DELIVERY_TYPE', 10),
        'package_type' => env('BOSTA_PACKAGE_TYPE', 'Parcel'),

        // Where to read values from in a response. Several candidates are
        // tried in order, so a renamed field does not break the integration.
        'response' => [
            'data_path' => 'data',
            'shipment_id' => ['_id', 'id', 'deliveryId'],
            'tracking_number' => ['trackingNumber', 'tracking_number', 'awb'],
            'state' => ['state.value', 'state', 'status', 'state.code'],
        ],

        'webhook' => [
            'data_path' => 'data',
            'signature_header' => env('BOSTA_WEBHOOK_HEADER', 'X-Bosta-Signature'),
        ],

        /*
        | Carrier state -> our shipment status.
        |
        | Matching is case-insensitive and falls back to a "contains" check, so
        | "Delivered to consignee" still maps through the "delivered" entry.
        | Our statuses are:
        | pending, assigned, picked_up, in_transit, delivered, failed,
        | returned, cancelled.
        */
        'state_map' => [
            'created' => 'assigned',
            'pending' => 'assigned',
            'picked up' => 'picked_up',
            'picked_up' => 'picked_up',
            'received at warehouse' => 'in_transit',
            'in transit' => 'in_transit',
            'out for delivery' => 'in_transit',
            'delivered' => 'delivered',
            'exception' => 'failed',
            'canceled' => 'cancelled',
            'cancelled' => 'cancelled',
            'returned to business' => 'returned',
            'returned' => 'returned',
        ],
    ],
];
