<?php

namespace App\Services\Ui;

use App\Services\Rules\RuleDefinitionService;
use App\Support\Tenant\TenantContext;

class UiRulesBridge
{
    public function __construct(
        private readonly RuleDefinitionService $ruleDefinitionService,
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

            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return null;
            }

            $context = app(TenantContext::class);
            $publicId = $resourceKey ?? (string) ($config['public_id'] ?? '');

            if ($publicId === '') {
                return null;
            }

            $definition = $this->ruleDefinitionService->find(
                $context->organization->id,
                $context->workspace?->id,
                $publicId,
            );

            return $definition?->toArray();
        } catch (\Throwable) {
            return null;
        }
    }
}
