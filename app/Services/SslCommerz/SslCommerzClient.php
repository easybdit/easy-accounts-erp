<?php

namespace App\Services\SslCommerz;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around SSLCommerz's v4 REST API — no third-party package,
 * just two HTTP calls (Section 90: no unnecessary dependency for something
 * this small). Credentials are read from config('services.sslcommerz'),
 * never passed in by a caller, so a payment session can't accidentally be
 * initiated against the wrong store.
 */
class SslCommerzClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.sslcommerz.sandbox')
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    /**
     * Starts a payment session. Returns SSLCommerz's raw response array;
     * on success it contains a 'GatewayPageURL' to redirect the customer
     * to. The caller must check $response['status'] === 'SUCCESS' before
     * trusting that URL.
     */
    public function initiateSession(array $data): array
    {
        $payload = [
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            ...$data,
        ];

        return Http::asForm()
            ->post("{$this->baseUrl}/gwprocess/v4/api.php", $payload)
            ->json() ?? [];
    }

    /**
     * Server-to-server validation of a completed transaction — the only
     * source of truth for whether a payment actually happened. Never trust
     * the success_url redirect alone; it's just a browser navigation from
     * an external site and can be reached without ever paying.
     */
    public function validateTransaction(string $valId): array
    {
        return Http::get("{$this->baseUrl}/validator/api/validationserverAPI.php", [
            'val_id' => $valId,
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'format' => 'json',
        ])->json() ?? [];
    }
}
