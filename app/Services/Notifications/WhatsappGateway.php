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
    public function send(string $phone, string $message, string $type = 'generic'): array
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
                'cloud_api' => $this->sendViaCloudApi($settings, $to, $message),
                'ultramsg' => $this->sendViaUltraMsg($settings, $to, $message),
                'twilio' => $this->sendViaTwilio($settings, $to, $message),
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

        Log::info('WhatsApp message sent', ['type' => $type, 'to' => $to]);

        return ['ok' => true, 'error' => null, 'response' => $response->json()];
    }

    private function sendViaCloudApi(StoreSetting $settings, string $to, string $message)
    {
        $base = rtrim($settings->whatsapp_api_url ?: 'https://graph.facebook.com/v20.0', '/');

        return Http::timeout(20)
            ->withToken($settings->whatsapp_api_token)
            ->acceptJson()
            ->post($base . '/' . $settings->whatsapp_sender_id . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                // Cloud API wants the number without a leading +.
                'to' => ltrim($to, '+'),
                'type' => 'text',
                'text' => ['preview_url' => true, 'body' => $message],
            ]);
    }

    private function sendViaUltraMsg(StoreSetting $settings, string $to, string $message)
    {
        $base = rtrim($settings->whatsapp_api_url ?: 'https://api.ultramsg.com', '/');

        return Http::timeout(20)
            ->asForm()
            ->post($base . '/' . $settings->whatsapp_sender_id . '/messages/chat', [
                'token' => $settings->whatsapp_api_token,
                'to' => $to,
                'body' => $message,
            ]);
    }

    private function sendViaTwilio(StoreSetting $settings, string $to, string $message)
    {
        $base = rtrim($settings->whatsapp_api_url ?: 'https://api.twilio.com/2010-04-01', '/');

        return Http::timeout(20)
            ->withBasicAuth($settings->whatsapp_sender_id, $settings->whatsapp_api_secret)
            ->asForm()
            ->post($base . '/Accounts/' . $settings->whatsapp_sender_id . '/Messages.json', [
                'From' => 'whatsapp:' . $this->normalisePhone($settings->whatsapp_from_number, $settings),
                'To' => 'whatsapp:' . $to,
                'Body' => $message,
            ]);
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
