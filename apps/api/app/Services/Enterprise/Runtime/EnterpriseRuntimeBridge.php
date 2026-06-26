<?php

namespace App\Services\Enterprise\Runtime;

use App\Modules\Sdk\Enterprise\Contracts\EnterpriseRuntimeContext;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;

class EnterpriseRuntimeBridge
{
    public function __construct(
        private readonly WorkspaceRuntimeProvider $runtimeProvider,
        private readonly EnterpriseRuntimeContextFactory $runtimeContextFactory,
    ) {
    }

    public function resolve(TenantContext $context): EnterpriseRuntimeContext
    {
        if (! (bool) config('heos.enterprise.runtime_aware', true)) {
            return $this->runtimeContextFactory->fromConfig();
        }

        try {
            $runtime = $this->runtimeProvider->resolve($context);

            return $this->runtimeContextFactory->fromRuntime($runtime);
        } catch (\Throwable) {
            return $this->runtimeContextFactory->fromConfig();
        }
    }

    public function requireCapability(TenantContext $context, string $capability): void
    {
        if (! $this->resolve($context)->capabilityEnabled($capability)) {
            throw new \App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException($capability);
        }
    }
}
