<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationMapper
{
    public function map(array $source, array $mapping, string $transformType): array;
}
