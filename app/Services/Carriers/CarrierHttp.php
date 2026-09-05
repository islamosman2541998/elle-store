<?php

namespace App\Services\Carriers;

use App\Models\ShippingCompany;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper that turns transport exceptions into a null response, so the
 * driver can treat "carrier unreachable" like any other failure.
 */
class CarrierHttp
{
    public function __construct(
        private readonly PendingRequest $client,
        private readonly ShippingCompany $company,
    ) {
    }

    public function post(string $url, array $payload): mixed
    {
        return $this->send(fn () => $this->client->post($url, $payload), $url);
    }

    public function get(string $url): mixed
    {
        return $this->send(fn () => $this->client->get($url), $url);
    }

    public function delete(string $url): mixed
    {
        return $this->send(fn () => $this->client->delete($url), $url);
    }

    private function send(callable $call, string $url): mixed
    {
        try {
            return $call();
        } catch (Throwable $e) {
            Log::warning('Carrier request failed', [
                'company' => $this->company->name,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
