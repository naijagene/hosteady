<?php

namespace App\Services\Ui;

use App\Services\Application\ApplicationRuntimeRegistryService;
use App\Support\Tenant\TenantContext;

class UiApplicationBridge
{
    public function __construct(
        private readonly ApplicationRuntimeRegistryService $applicationRegistry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function resolveReferenceBestEffort(?string $moduleKey, ?string $resourceKey, array $config = []): ?array
    {
        try {
            if (! app()->bound(TenantContext::class)) {
                return null;
            }

            $context = app(TenantContext::class);
            $publicId = $resourceKey
                ?? (string) ($config['application_public_id'] ?? $config['public_id'] ?? '');

            if ($publicId === '') {
                return null;
            }

            $definition = $this->applicationRegistry->findByPublicId(
                $context->organization->id,
                $context->workspace?->id,
                $publicId,
            );

            $payload = $definition->toArray();

            if ($moduleKey !== null && $moduleKey !== '') {
                $payload['module_key'] = $moduleKey;
            }

            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }
}
