<?php

namespace App\Services\Module;

use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\ModuleRegistry;
use App\Services\Module\Data\ModuleInspectionResult;
use App\Services\Module\Data\ModuleInspectionSummary;
use App\Services\Module\Data\PlatformModuleHealthContext;

class ModuleInspectionService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleHealthAggregator $healthAggregator,
    ) {
    }

    public function inspect(string $moduleKey): ?ModuleInspectionResult
    {
        $module = $this->registry->findByKey($moduleKey);

        if ($module === null) {
            return null;
        }

        return $this->buildInspectionResult($module);
    }

    /**
     * @return list<ModuleInspectionResult>
     */
    public function inspectAll(): array
    {
        $results = [];

        foreach ($this->sortedModules() as $module) {
            $results[] = $this->buildInspectionResult($module);
        }

        return $results;
    }

    public function summary(): ModuleInspectionSummary
    {
        return $this->buildSummary($this->inspectAll());
    }

    /**
     * @return array<string, int|list<string>>
     */
    public function statistics(): array
    {
        $modules = $this->inspectAll();
        $summary = $this->buildSummary($modules);

        $permissionCount = 0;
        $settingCount = 0;
        $capabilityCount = 0;

        foreach ($modules as $module) {
            $permissionCount += count($module->permissions);
            $settingCount += count($module->settings);
            $capabilityCount += count($module->capabilities);
        }

        return [
            'module_count' => $summary->moduleCount,
            'healthy_count' => $summary->healthyCount,
            'warning_count' => $summary->warningCount,
            'critical_count' => $summary->criticalCount,
            'runtime_contributor_count' => $summary->runtimeContributorCount,
            'lifecycle_supported_count' => $summary->lifecycleSupportedCount,
            'permission_count' => $permissionCount,
            'setting_count' => $settingCount,
            'capability_count' => $capabilityCount,
            'module_keys' => array_map(fn (ModuleInspectionResult $module) => $module->moduleKey, $modules),
        ];
    }

    /**
     * @return list<ApplicationModule>
     */
    private function sortedModules(): array
    {
        $modules = $this->registry->all();

        usort($modules, fn (ApplicationModule $left, ApplicationModule $right) => $left->key() <=> $right->key());

        return $modules;
    }

    private function buildInspectionResult(ApplicationModule $module): ModuleInspectionResult
    {
        $manifest = $module->manifest();
        $health = $module->health(new PlatformModuleHealthContext);

        return new ModuleInspectionResult(
            moduleKey: $module->key(),
            moduleUuid: $manifest->moduleUuid,
            name: $module->name(),
            version: $module->version(),
            manifestVersion: $manifest->manifestVersion,
            dependencies: array_map(
                fn ($dependency) => $dependency->key,
                $manifest->dependencies,
            ),
            capabilities: $manifest->capabilities,
            permissions: array_map(
                fn ($permission) => $permission->key,
                $manifest->permissions,
            ),
            settings: array_map(
                fn ($setting) => $setting->settingKey,
                $manifest->settings,
            ),
            lifecycleSupported: $this->supportsLifecycle($module),
            runtimeContributor: $this->supportsRuntimeContribution($module),
            syncSupported: true,
            healthStatus: $health->status,
            healthMessage: $health->message,
        );
    }

    /**
     * @param  list<ModuleInspectionResult>  $modules
     */
    private function buildSummary(array $modules): ModuleInspectionSummary
    {
        $healthyCount = 0;
        $warningCount = 0;
        $criticalCount = 0;
        $runtimeContributorCount = 0;
        $lifecycleSupportedCount = 0;

        foreach ($modules as $module) {
            match ($module->healthStatus) {
                'critical' => $criticalCount++,
                'warning' => $warningCount++,
                default => $healthyCount++,
            };

            if ($module->runtimeContributor) {
                $runtimeContributorCount++;
            }

            if ($module->lifecycleSupported) {
                $lifecycleSupportedCount++;
            }
        }

        return new ModuleInspectionSummary(
            moduleCount: count($modules),
            healthyCount: $healthyCount,
            warningCount: $warningCount,
            criticalCount: $criticalCount,
            runtimeContributorCount: $runtimeContributorCount,
            lifecycleSupportedCount: $lifecycleSupportedCount,
            modules: $modules,
        );
    }

    private function supportsLifecycle(ApplicationModule $module): bool
    {
        return is_subclass_of($module, AbstractApplicationModule::class)
            || in_array(ApplicationModule::class, class_implements($module) ?: [], true);
    }

    private function supportsRuntimeContribution(ApplicationModule $module): bool
    {
        $method = new \ReflectionMethod($module, 'contributeRuntime');

        return $method->getDeclaringClass()->getName() !== AbstractApplicationModule::class;
    }
}
