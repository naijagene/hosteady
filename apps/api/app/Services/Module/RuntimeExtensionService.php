<?php

namespace App\Services\Module;

use App\Modules\Sdk\Contracts\ModuleRuntimeContext;
use App\Modules\Sdk\ModuleRegistry;
use App\Modules\Sdk\Runtime\RuntimeExtensionManager;
use App\Modules\Sdk\Runtime\RuntimePipelineReport;
use App\Support\Tenant\TenantContext;

class RuntimeExtensionService
{
    public function __construct(
        private readonly ModuleRegistry $moduleRegistry,
        private readonly RuntimeExtensionManager $extensionManager,
        private readonly RuntimeContributionAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @param  list<string>  $activeModuleKeys
     */
    public function resolveForTenant(TenantContext $context, array $activeModuleKeys): RuntimePipelineReport
    {
        $contributors = [];

        foreach ($activeModuleKeys as $moduleKey) {
            $module = $this->moduleRegistry->findByKey($moduleKey);

            if ($module === null) {
                continue;
            }

            $contributors[] = new ApplicationModuleRuntimeContributor($module);
        }

        $report = $this->extensionManager->resolve(
            new TenantModuleRuntimeContext($context),
            $contributors,
        );

        foreach ($report->results as $result) {
            if ($result->skipped || ! $result->success) {
                continue;
            }

            $this->auditRecorder->recordContribution($context, $result->moduleKey);
        }

        return $report;
    }
}

class TenantModuleRuntimeContext implements ModuleRuntimeContext
{
    public function __construct(
        private readonly TenantContext $context,
    ) {
    }

    public function organizationPublicId(): string
    {
        return $this->context->organizationPublicId;
    }

    public function workspacePublicId(): ?string
    {
        return $this->context->workspacePublicId;
    }
}
