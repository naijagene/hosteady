<?php

namespace App\Services\Navigation;

use App\Services\Rules\RuleDefinitionService;
use App\Support\Tenant\TenantContext;

class NavigationRulesBridge
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
            $publicId = $resourceKey ?? (string) ($config['public_id'] ?? $config['rule_public_id'] ?? '');

            if ($publicId === '') {
                return null;
            }

            $definition = $this->ruleDefinitionService->find(
                $context->organization->id,
                $context->workspace?->id,
                $publicId,
            );

            $payload = $definition?->toArray();

            if ($payload !== null && $moduleKey !== null && $moduleKey !== '') {
                $payload['module_key'] = $moduleKey;
            }

            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }

    public function evaluateVisibilityBestEffort(TenantContext $context, array $conditions): bool
    {
        try {
            if ($conditions === []) {
                return true;
            }

            return app(NavigationVisibilityResolverService::class)->evaluate($context, $conditions);
        } catch (\Throwable) {
            return true;
        }
    }
}
