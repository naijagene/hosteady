<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationTransformer
{
    public function transform(array $payload, string $transformType, array $config): array;
}
