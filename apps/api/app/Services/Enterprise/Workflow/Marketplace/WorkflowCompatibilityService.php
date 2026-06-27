<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\WorkflowPackage;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowCompatibilityStatus;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageNotFoundException;

class WorkflowCompatibilityService
{
    /**
     * @var array<string, string>
     */
    private const CAPABILITY_MAP = [
        'workflow' => 'heos.enterprise.workflow.enabled',
        'workflow_designer' => 'heos.enterprise.workflow_designer.enabled',
        'notifications' => 'heos.enterprise.notifications.enabled',
        'scheduler' => 'heos.enterprise.scheduler.enabled',
        'jobs' => 'heos.enterprise.jobs.enabled',
        'automation' => 'heos.enterprise.automation.enabled',
        'search' => 'heos.enterprise.search.enabled',
    ];

    public function check(EnterpriseScope $scope, string $packagePublicId): WorkflowCompatibilityReport
    {
        $package = $this->findPackage($scope, $packagePublicId);
        $version = $package->versions()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->first();

        if ($version === null) {
            return new WorkflowCompatibilityReport(
                packagePublicId: $package->public_id,
                status: WorkflowCompatibilityStatus::Unsupported->value,
                issues: ['No published package version is available.'],
            );
        }

        return $this->assessManifest(
            $scope,
            WorkflowPackageManifest::fromArray($version->manifest_json),
            $package->public_id,
        );
    }

    public function assessManifest(
        EnterpriseScope $scope,
        WorkflowPackageManifest $manifest,
        ?string $packagePublicId = null,
    ): WorkflowCompatibilityReport {
        $issues = [];
        $warnings = [];

        if ($manifest->engine !== 'heos') {
            $issues[] = sprintf('Unsupported engine [%s].', $manifest->engine);
        }

        if ($manifest->engineVersion !== null && ! $this->engineVersionCompatible($manifest->engineVersion)) {
            $issues[] = sprintf('Engine version constraint [%s] is not satisfied.', $manifest->engineVersion);
        }

        if ($manifest->canvas !== [] && ! (bool) config('heos.enterprise.workflow_designer.enabled', true)) {
            $issues[] = 'Workflow designer capability is required for canvas payload.';
        }

        foreach ($manifest->requires as $dependency) {
            if ($dependency->type === 'capability') {
                $configKey = self::CAPABILITY_MAP[$dependency->key] ?? null;

                if ($configKey === null) {
                    if ($dependency->required) {
                        $issues[] = sprintf('Required capability [%s] is not recognized.', $dependency->key);
                    } else {
                        $warnings[] = sprintf('Optional capability [%s] is not recognized.', $dependency->key);
                    }

                    continue;
                }

                if (! (bool) config($configKey, true)) {
                    if ($dependency->required) {
                        $issues[] = sprintf('Required capability [%s] is disabled.', $dependency->key);
                    } else {
                        $warnings[] = sprintf('Optional capability [%s] is disabled.', $dependency->key);
                    }
                }
            } elseif ($dependency->type === 'module' && $dependency->required) {
                $warnings[] = sprintf('Module dependency [%s] cannot be auto-verified in this slice.', $dependency->key);
            } elseif ($dependency->type === 'package' && $dependency->required) {
                $issues[] = sprintf('Required package dependency [%s] is not installed.', $dependency->key);
            }
        }

        if (! (bool) config('heos.enterprise.workflow.enabled', true)) {
            $issues[] = 'Workflow capability is disabled.';
        }

        $status = match (true) {
            $issues !== [] => WorkflowCompatibilityStatus::Unsupported->value,
            $warnings !== [] => WorkflowCompatibilityStatus::Warning->value,
            default => WorkflowCompatibilityStatus::Compatible->value,
        };

        return new WorkflowCompatibilityReport(
            packagePublicId: $packagePublicId ?? '',
            status: $status,
            issues: $issues,
            warnings: $warnings,
            dependencies: $manifest->requires,
        );
    }

    private function engineVersionCompatible(string $constraint): bool
    {
        if (str_starts_with($constraint, '>=')) {
            $required = ltrim($constraint, '>=');

            return version_compare('4.0.0', $required, '>=') || version_compare('4.0.0', $required, '=');
        }

        return true;
    }

    private function findPackage(EnterpriseScope $scope, string $packagePublicId): WorkflowPackage
    {
        $organizationId = \App\Models\Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $package = WorkflowPackage::query()
            ->with(['versions', 'dependencies'])
            ->where('public_id', $packagePublicId)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
            })
            ->first();

        if ($package === null) {
            throw new WorkflowPackageNotFoundException(sprintf('Workflow package [%s] was not found.', $packagePublicId));
        }

        return $package;
    }
}
