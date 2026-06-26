<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

interface EnterpriseRuntimeContext
{
    public function runtimeVersion(): string;

    public function capabilityEnabled(string $capability): bool;

    public function featureFlag(string $key, mixed $default = null): mixed;

    /**
     * @return array<string, mixed>
     */
    public function moduleMetadata(string $moduleKey): array;

    /**
     * @return array<string, mixed>
     */
    public function enterpriseMetadata(): array;
}
