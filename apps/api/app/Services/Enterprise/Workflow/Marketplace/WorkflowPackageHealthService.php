<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class WorkflowPackageHealthService
{
    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly WorkflowPackageStatisticsService $statisticsService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.workflow_marketplace.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            ['workflow_packages', 'workflow_package_versions', 'workflow_package_installs'],
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $warnings = [];
        $status = 'healthy';

        $organizationId = $context?->organization->id;
        $workspaceId = $context?->workspace->id;

        $scope = $context !== null
            ? new EnterpriseScope(
                organizationPublicId: $context->organizationPublicId,
                workspacePublicId: $context->workspacePublicId,
            )
            : new EnterpriseScope(organizationPublicId: '', workspacePublicId: null);

        $stats = $this->statisticsService->statistics($scope, $organizationId, $workspaceId);

        if (! $enabled) {
            $warnings[] = 'Workflow marketplace is disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $stats->packages === 0) {
            $warnings[] = 'No workflow marketplace packages exist yet.';
            $status = 'warning';
        }

        return [
            'enabled' => $enabled,
            'packages' => $stats->packages,
            'installs' => $stats->installs,
            'versions' => $stats->versions,
            'updates' => $stats->updatesAvailable,
            'warnings' => $warnings,
            'status' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'packages' => 0,
            'installs' => 0,
            'versions' => 0,
            'updates' => 0,
            'warnings' => [],
            'status' => 'critical',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $assessment = $this->assess($context);
        $stats = $this->statisticsService->statistics(
            $context !== null
                ? new EnterpriseScope(
                    organizationPublicId: $context->organizationPublicId,
                    workspacePublicId: $context->workspacePublicId,
                )
                : new EnterpriseScope(organizationPublicId: '', workspacePublicId: null),
            $context?->organization->id,
            $context?->workspace->id,
        );

        return [
            'enabled' => $assessment['enabled'],
            'packages' => $assessment['packages'],
            'installed' => $assessment['installs'],
            'updates_available' => $stats->updatesAvailable,
            'featured' => $stats->featured,
            'compatible' => $stats->compatible,
        ];
    }
}
