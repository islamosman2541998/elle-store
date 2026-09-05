<?php

namespace App\Services;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use App\Models\TrackingEventLog;
use Illuminate\Support\Facades\Log;

class MetaCapiService
{
    public function sendEvent(string $eventName, array $payload = [], ?string $eventId = null): void
    {
        $settings = StoreSetting::current();

        if (! $settings->tracking_enabled) {
            return;
        }

        if (! $settings->meta_pixel_id || ! $settings->meta_capi_access_token) {
            return;
        }

        $eventId = $eventId ?: app(TrackingEventService::class)->eventId(strtolower($eventName));

        $data = [
            'data' => [
                [
                    'event_name' => $eventName,
                    'event_time' => time(),
                    'event_id' => $eventId,
                    'action_source' => 'website',
                    'event_source_url' => Request::fullUrl(),
                    'user_data' => $this->userData(),
                    'custom_data' => $this->customData($payload),
                ],
            ],
        ];

        if ($settings->meta_test_event_code) {
            $data['test_event_code'] = $settings->meta_test_event_code;
        }

        $log = TrackingEventLog::query()->create([
            'event_name' => $eventName,
            'event_id' => $eventId,
            'source' => 'server',
            'platform' => 'meta_capi',
            'status' => 'pending',
            'payload' => $data,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'order_id' => $payload['order_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
        ]);

        try {
            $response = Http::post(
                "https://graph.facebook.com/v19.0/{$settings->meta_pixel_id}/events?access_token={$settings->meta_capi_access_token}",
                $data
            );

            $log->update([
                'status' => $response->successful() ? 'success' : 'failed',
                'response' => [
                    'status' => $response->status(),
                    'body' => $response->json() ?: $response->body(),
                ],
                'sent_at' => now(),
                'error_message' => $response->successful() ? null : $response->body(),
            ]);

            if (! $response->successful()) {
                Log::warning('Meta CAPI event failed', [
                    'event_name' => $eventName,
                    'event_id' => $eventId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'response' => null,
                'sent_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Meta CAPI exception', [
                'event_name' => $eventName,
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function userData(): array
    {
        $data = [
            'client_ip_address' => Request::ip(),
            'client_user_agent' => Request::userAgent(),
        ];

        if (request()->cookie('_fbp')) {
            $data['fbp'] = request()->cookie('_fbp');
        }

        if (request()->cookie('_fbc')) {
            $data['fbc'] = request()->cookie('_fbc');
        }

        return array_filter($data);
    }

    protected function customData(array $payload): array
    {
        return array_filter([
            'currency' => $payload['currency'] ?? StoreSetting::current()->currency_code ?? 'EGP',
            'value' => isset($payload['value']) ? (float) $payload['value'] : null,
            'content_ids' => $payload['content_ids'] ?? null,
            'content_type' => $payload['content_type'] ?? null,
            'content_name' => $payload['content_name'] ?? null,
            'num_items' => $payload['num_items'] ?? null,
            'order_id' => $payload['order_id'] ?? null,
            'search_string' => $payload['search_string'] ?? null,
        ], fn($value) => $value !== null && $value !== '');
    }
}