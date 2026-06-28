<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Contracts\IntegrationWebhookVerifier;

class IntegrationWebhookVerifierService implements IntegrationWebhookVerifier
{
    public function verify(string $authType, array $headers, string $payload, array $config): bool
    {
        return match ($authType) {
            'none' => true,
            'shared_secret' => $this->verifySharedSecret($headers, $config),
            'hmac_sha256' => $this->verifyHmac($headers, $payload, $config),
            default => false,
        };
    }

    private function verifySharedSecret(array $headers, array $config): bool
    {
        $expected = (string) ($config['secret'] ?? '');
        $provided = (string) ($headers['x-heos-secret'] ?? $headers['X-Heos-Secret'] ?? '');

        return $expected !== '' && hash_equals($expected, $provided);
    }

    private function verifyHmac(array $headers, string $payload, array $config): bool
    {
        $secret = (string) ($config['secret'] ?? '');
        $signature = (string) ($headers['x-heos-signature'] ?? $headers['X-Heos-Signature'] ?? '');

        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
