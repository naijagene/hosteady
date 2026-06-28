<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationWebhookVerifier
{
    public function verify(string $authType, array $headers, string $payload, array $config): bool;
}
