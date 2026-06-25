<?php

namespace App\Services\Runtime;

use App\Enums\RuntimeHealthStatus;
use App\Services\Runtime\Data\WorkspaceRuntimeHealth;
use App\Support\Tenant\TenantContext;

class RuntimeHealthService
{
    public function __construct(
        private readonly RuntimeDiagnosticsService $diagnosticsService,
        private readonly RuntimeIntegrityValidator $integrityValidator,
        private readonly RuntimeDependencyValidator $dependencyValidator,
    ) {
    }

    public function assess(
        TenantContext $context,
        ?string $activeWorkspaceApplicationPublicId = null,
    ): WorkspaceRuntimeHealth {
        $diagnostics = $this->diagnosticsService->diagnose($context, $activeWorkspaceApplicationPublicId);
        $integrity = $this->integrityValidator->validate($context);
        $dependency = $this->dependencyValidator->validate($context);

        $health = RuntimeHealthStatus::worst(
            $diagnostics->healthStatus,
            $integrity->status,
            $dependency->status,
        );

        return new WorkspaceRuntimeHealth(
            health: $health,
            diagnostics: $diagnostics,
            integrity: $integrity,
            cache: $diagnostics->cache,
            dependencySummary: $dependency->summary(),
            recommendations: $diagnostics->recommendations,
        );
    }
}
