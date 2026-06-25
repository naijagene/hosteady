<?php

namespace App\Services\Runtime;

use App\Enums\OrganizationApplicationStatus;
use App\Enums\RuntimeHealthStatus;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceApplicationStatus;
use App\Models\ApplicationSettingDefinition;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationSetting;
use App\Services\Application\ApplicationSettingsRegistry;
use App\Services\Runtime\Data\RuntimeDependencyReport;
use App\Services\Runtime\Data\RuntimeManifest;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use App\Support\Tenant\TenantContext;

class RuntimeDependencyValidator
{
    public function __construct(
        private readonly WorkspaceRuntimeResolver $runtimeResolver,
    ) {
    }

    public function validate(TenantContext $context): RuntimeDependencyReport
    {
        $manifest = $this->runtimeResolver->buildManifest($context);
        $activeKeys = [];

        foreach ($manifest->applications as $application) {
            $activeKeys[$application->key] = true;
        }

        $missing = [];
        $disabled = [];
        $circular = [];
        $duplicates = [];
        $versionMismatches = [];
        $warnings = [];

        foreach ($manifest->applications as $application) {
            $seen = [];

            foreach ($application->dependencies as $dependencyKey) {
                if (isset($seen[$dependencyKey])) {
                    $duplicates[] = sprintf('%s declares duplicate dependency [%s].', $application->key, $dependencyKey);
                }

                $seen[$dependencyKey] = true;

                if (! isset($activeKeys[$dependencyKey])) {
                    if ($this->dependencyExistsButDisabled($context, $dependencyKey)) {
                        $disabled[] = sprintf('%s depends on disabled application [%s].', $application->key, $dependencyKey);
                    } else {
                        $missing[] = sprintf('%s depends on missing application [%s].', $application->key, $dependencyKey);
                    }
                }
            }
        }

        $circular = $this->detectCircularDependencies($manifest);

        $errors = array_values(array_merge($missing, $disabled, $circular, $duplicates));

        $status = RuntimeHealthStatus::Healthy;

        if ($duplicates !== [] || $circular !== []) {
            $status = RuntimeHealthStatus::Critical;
        } elseif ($missing !== [] || $disabled !== []) {
            $status = RuntimeHealthStatus::Warning;
        }

        return new RuntimeDependencyReport(
            status: $status,
            missingDependencies: $missing,
            disabledDependencies: $disabled,
            circularDependencies: $circular,
            duplicateDependencies: $duplicates,
            versionMismatches: $versionMismatches,
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * @return list<string>
     */
    private function detectCircularDependencies(RuntimeManifest $manifest): array
    {
        $graph = [];

        foreach ($manifest->applications as $application) {
            $graph[$application->key] = $application->dependencies;
        }

        $cycles = [];
        $visited = [];
        $stack = [];

        foreach (array_keys($graph) as $node) {
            $this->visitDependencyNode($node, $graph, $visited, $stack, $cycles);
        }

        return array_values(array_unique($cycles));
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  array<string, bool>  $visited
     * @param  array<string, bool>  $stack
     * @param  list<string>  $cycles
     */
    private function visitDependencyNode(
        string $node,
        array $graph,
        array &$visited,
        array &$stack,
        array &$cycles,
    ): void {
        if (isset($stack[$node])) {
            $cycles[] = sprintf('Circular dependency detected involving [%s].', $node);

            return;
        }

        if (isset($visited[$node])) {
            return;
        }

        $visited[$node] = true;
        $stack[$node] = true;

        foreach ($graph[$node] ?? [] as $dependency) {
            if (isset($graph[$dependency])) {
                $this->visitDependencyNode($dependency, $graph, $visited, $stack, $cycles);
            }
        }

        unset($stack[$node]);
    }

    private function dependencyExistsButDisabled(TenantContext $context, string $dependencyKey): bool
    {
        return WorkspaceApplication::query()
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->whereNull('deleted_at')
            ->where('status', '!=', WorkspaceApplicationStatus::Removed)
            ->whereHas('application', fn ($query) => $query->where('key', $dependencyKey))
            ->where(function ($query) {
                $query->where('status', '!=', WorkspaceApplicationStatus::Active)
                    ->orWhereHas('organizationApplication', fn ($orgQuery) => $orgQuery
                        ->where('status', '!=', OrganizationApplicationStatus::Active));
            })
            ->exists();
    }
}
