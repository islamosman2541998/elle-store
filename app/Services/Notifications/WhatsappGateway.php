<?php

namespace App\Services\Notifications;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends WhatsApp messages through whichever provider the shop configured.
 *
 * Three providers are supported out of the box. Which fields each one needs is
 * described by requirements(), and the dashboard only asks for those.
 *
 * Sending never throws: a provider outage must not break an order.
 */
class WhatsappGateway
{
    public const PROVIDERS = ['cloud_api', 'ultramsg', 'twilio'];

    /**
     * Which settings each provider needs before it can send.
     *
     * @return array<string, array<int, string>>
     */
    public static function requirements(): array
    {
        return [
            // Meta WhatsApp Cloud API: permanent token + phone number id.
            'cloud_api' => ['whatsapp_api_token', 'whatsapp_sender_id'],
            // UltraMsg: instance id + token.
            'ultramsg' => ['whatsapp_api_token', 'whatsapp_sender_id'],
            // Twilio: account SID + auth token + the WhatsApp sender number.
            'twilio' => ['whatsapp_sender_id', 'whatsapp_api_secret', 'whatsapp_from_number'],
        ];
    }

    public static function providerOptions(): array
    {
        return [
            'cloud_api' => 'Meta WhatsApp Cloud API',
            'ultramsg' => 'UltraMsg',
            'twilio' => 'Twilio',
        ];
    }

    /** Everything the chosen provider needs is present. */
    public function isConfigured(?StoreSetting $settings = null): bool
    {
        $settings ??= StoreSetting::current();
        $provider = $settings->whatsapp_api_provider;

        if (blank($provider) || ! in_array($provider, self::PROVIDERS, true)) {
            return false;
        }

        foreach (self::requirements()[$provider] as $field) {
            if (blank($settings->{$field})) {
                return false;
            }
        }

        return true;
    }

    /** Which required fields are still empty, for the dashboard to report. */
    public function missingFields(?StoreSetting $settings = null): array
    {
        $settings ??= StoreSetting::current();
        $provider = $settings->whatsapp_api_provider;

        if (blank($provider) || ! isset(self::requirements()[$provider])) {
            return ['whatsapp_api_provider'];
        }

        return array_values(array_filter(
            self::requirements()[$provider],
            fn (string $field) => blank($settings->{$field})
        ));
    }

    /**
     * Send one message.
     *
     * @return array{ok: bool, error: ?string, response: mixed}
     */
    public function send(string $phone, string $message, string $type = 'generic', ?string $imageUrl = null): array
    {
        $settings = StoreSetting::current();

        if (! $this->isConfigured($settings)) {
            return $this->fail('WhatsApp is not configured.', $type);
        }

        $to = $this->normalisePhone($phone, $settings);

        if (blank($to)) {
            return $this->fail('No usable phone number.', $type);
        }

        try {
            $response = match ($settings->whatsapp_api_provider) {
                'cloud_api' => $this->sendViaCloudApi($settings, $to, $message, $imageUrl),
                'ultramsg' => $this->sendViaUltraMsg($settings, $to, $message, $imageUrl),
                'twilio' => $this->sendViaTwilio($settings, $to, $message, $imageUrl),
                default => null,
            };
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), $type);
        }

        if ($response === null) {
            return $this->fail('Unknown WhatsApp provider.', $type);
        }

        if ($response->failed()) {
            return $this->fail(
                'HTTP ' . $response->status() . ' ' . $this->providerError($response->json()),
                $type,
                $response->json()
            );
        }

        // Not every provider uses the status code to report a refusal.
        // UltraMsg in particular answers 200 with {"error": "..."} , which
        // would otherwise be recorded as a successful send.
        $rejection = $this->rejection($settings->whatsapp_api_provider, $response->json());

        if ($rejection !== null) {
            return $this->fail($rejection, $type, $response->json());
        }

        Log::info('WhatsApp message sent', ['type' => $type, 'to' => $to]);

        return ['ok' => true, 'error' => null, 'response' => $response->json()];
    }

    /**
     * A refusal hidden inside a 2xx body, or null when the send really worked.
     */
    private function rejection(string $provider, mixed $body): ?string
    {
        $error = match ($provider) {
            // {"error": "Instance not found"} comes back with HTTP 200.
            'ultramsg' => data_get($body, 'error'),
            // Twilio reports a rejected message with a numeric code.
            'twilio' => data_get($body, 'code') ? data_get($body, 'message') : null,
            'cloud_api' => data_get($body, 'error.message'),
            default => null,
        };

        if (blank($error)) {
            return null;
        }

        return is_string($error) ? $error : json_encode($error, JSON_UNESCAPED_UNICODE);
    }

    private function sendViaCloudApi(StoreSetting $settings, string $to, string $message, ?string $imageUrl = null)
    {
        $base = rtrim($settings->whatsapp_api_url ?: 'https://graph.facebook.com/v20.0', '/');

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            // Cloud API wants the number without a leading +.
            'to' => ltrim($to, '+'),
        ];

        // With an image, the text becomes the caption - one message, not two.
        $payload += $imageUrl
            ? ['type' => 'image', 'image' => ['link' => $imageUrl, 'caption' => $message]]
            : ['type' => 'text', 'text' => ['preview_url' => true, 'body' => $message]];

        return Http::timeout(20)
            ->withToken($settings->whatsapp_api_token)
            ->acceptJson()
            ->post($base . '/' . $settings->whatsapp_sender_id . '/messages', $payload);
    }

    private function sendViaUltraMsg(StoreSetting $settings, string $to, string $message, ?string $imageUrl = null)
    {
        $base = rtrim($settings->whatsapp_api_url ?: 'https://api.ultramsg.com', '/');
        $instance = $base . '/' . $settings->whatsapp_sender_id;

        // UltraMsg puts images on their own endpoint, with the text as caption.
        $endpoint = $imageUrl ? '/messages/image' : '/messages/chat';

        $payload = [
            'token' => $settings->whatsapp_api_token,
            'to' => $to,
        ];

        $payload += $imageUrl
            ? ['image' => $imageUrl, 'caption' => $message]
            : ['body' => $message];

        return Http::timeout(20)->asForm()->post($instance . $endpoint, $payload);
    }

    private function sendViaTwilio(StoreSetting $settings, string $to, string $message, ?string $imageUrl = null)
    {
        $base = rtrim($settings->whatsapp_api_url ?: 'https://api.twilio.com/2010-04-01', '/');

        $payload = [
            'From' => 'whatsapp:' . $this->normalisePhone($settings->whatsapp_from_number, $settings),
            'To' => 'whatsapp:' . $to,
            'Body' => $message,
        ];

        if ($imageUrl) {
            $payload['MediaUrl'] = $imageUrl;
        }

        return Http::timeout(20)
            ->withBasicAuth($settings->whatsapp_sender_id, $settings->whatsapp_api_secret)
            ->asForm()
            ->post($base . '/Accounts/' . $settings->whatsapp_sender_id . '/Messages.json', $payload);
    }

    /**
     * Turn a locally written number into international form.
     *
     * Egyptian numbers are typed as 01012345678; WhatsApp needs +201012345678.
     */
    public function normalisePhone(?string $phone, ?StoreSetting $settings = null): string
    {
        if (blank($phone)) {
            return '';
        }

        $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = '+' . substr($digits, 2);
        }

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        // Local Egyptian mobile: 01xxxxxxxxx
        if (str_starts_with($digits, '01') && strlen($digits) >= 11) {
            return '+2' . $digits;
        }

        // Already carries the country code.
        if (str_starts_with($digits, '20')) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }

    private function providerError(mixed $body): string
    {
        $message = data_get($body, 'error.message')
            ?? data_get($body, 'message')
            ?? data_get($body, 'error');

        return is_string($message) ? $message : '';
    }

    private function fail(string $error, string $type, mixed $response = null): array
    {
        Log::warning('WhatsApp message not sent', ['type' => $type, 'error' => $error]);

        return ['ok' => false, 'error' => $error, 'response' => $response];
    }
}
