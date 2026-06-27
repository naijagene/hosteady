<?php

namespace App\Services\Module;

use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\ModuleRegistry;
use App\Services\Module\Data\ModuleDoctorReport;

class ModuleDoctorService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleValidationService $validationService,
        private readonly ModuleDependencyGraphService $dependencyGraphService,
        private readonly ModuleHealthAggregator $healthAggregator,
        private readonly ModuleInspectionService $inspectionService,
        private readonly ModuleDeveloperAuditRecorder $auditRecorder,
    ) {
    }

    public function diagnose(): ModuleDoctorReport
    {
        $validation = $this->validationService->validate();
        $graph = $this->dependencyGraphService->build();
        $health = $this->healthAggregator->aggregate();
        $inspection = $this->inspectionService->summary();

        $errors = [];
        $warnings = [];
        $recommendations = [];

        foreach ($validation->issues as $issue) {
            $errors[] = sprintf('[%s] %s', $issue->code, $issue->message);
        }

        if ($graph->cycles !== []) {
            foreach ($graph->cycles as $cycleNode) {
                $errors[] = sprintf('[circular_dependency] Module "%s" participates in a circular dependency.', $cycleNode);
            }
        }

        foreach ($health->moduleHealth as $moduleKey => $moduleHealth) {
            if ($moduleHealth['status'] === 'critical') {
                $errors[] = sprintf('[module_health] Module "%s" reported critical health.', $moduleKey);
            } elseif ($moduleHealth['status'] === 'warning') {
                $warnings[] = sprintf('[module_health] Module "%s" reported warning health.', $moduleKey);
            }
        }

        if ($health->syncHealth['missing'] > 0) {
            $warnings[] = sprintf(
                '[sync_health] %d registered module(s) are missing from the application catalog.',
                $health->syncHealth['missing'],
            );
            $recommendations[] = 'Run `php artisan heos:sync-modules` to synchronize module manifests into the catalog.';
        }

        if ($inspection->runtimeContributorCount === 0) {
            $recommendations[] = 'Consider implementing runtime contributions for active modules.';
        }

        if (! config('heos.sync.on_seed', true)) {
            $recommendations[] = 'Enable HEOS_MODULE_SYNC_ON_SEED for automatic catalog synchronization during seeding.';
        }

        $enterpriseHealth = [
            'storage' => app(\App\Services\Enterprise\FileMedia\EnterpriseStorageHealthService::class)->assess(),
            'jobs' => app(\App\Services\Enterprise\Jobs\PlatformJobHealthService::class)->assess(),
            'scheduler' => app(\App\Services\Enterprise\Scheduler\SchedulerHealthService::class)->assess(),
            'search' => app(\App\Services\Enterprise\Search\SearchHealthService::class)->assess(),
            'workflow' => app(\App\Services\Enterprise\Workflow\WorkflowHealthService::class)->assess(),
            'business_modules' => app(\App\Services\Module\Development\BusinessModuleHealthService::class)->assess(),
        ];

        $this->collectEnterpriseHealthWarnings($enterpriseHealth, $warnings, $errors);

        $exitCode = match (true) {
            $errors !== [] => 2,
            $warnings !== [] => 1,
            default => 0,
        };

        $report = new ModuleDoctorReport(
            platformSummary: [
                'platform' => 'HEOS',
                'module_count' => count($this->registry->all()),
                'manifest_version' => \App\Modules\Sdk\Data\ModuleManifest::CURRENT_MANIFEST_VERSION,
                'sync_on_seed' => (bool) config('heos.sync.on_seed', true),
                'overall_status' => $health->overallStatus,
                'enterprise' => [
                    'event_bus' => (bool) config('heos.enterprise.event_bus.enabled', true),
                    'notifications' => (bool) config('heos.enterprise.notifications.enabled', true),
                    'reference_data' => (bool) config('heos.enterprise.reference_data.enabled', true),
                    'files' => (bool) config('heos.enterprise.files.enabled', true),
                    'runtime_aware' => (bool) config('heos.enterprise.runtime_aware', true),
                    ...$enterpriseHealth,
                ],
            ],
            modules: array_map(
                fn ($module) => $module->toArray(),
                $inspection->modules,
            ),
            validation: $validation,
            dependencyGraph: $graph->exportGraph(),
            lifecycleSupport: $this->capabilityMap(
                fn (ApplicationModule $module) => $this->supportsLifecycle($module),
            ),
            runtimeContributionSupport: $this->capabilityMap(
                fn (ApplicationModule $module) => $this->supportsRuntimeContribution($module),
            ),
            syncSupport: [
                'available' => true,
                'synced' => $health->syncHealth['synced'],
                'missing' => $health->syncHealth['missing'],
            ],
            health: $health,
            warnings: $warnings,
            errors: $errors,
            recommendations: $recommendations,
            exitCode: $exitCode,
        );

        $this->auditRecorder->recordDoctorExecuted($report);

        return $report;
    }

    /**
     * @param  callable(ApplicationModule): bool  $resolver
     * @return array<string, bool>
     */
    private function capabilityMap(callable $resolver): array
    {
        $map = [];

        foreach ($this->registry->all() as $module) {
            $map[$module->key()] = $resolver($module);
        }

        ksort($map);

        return $map;
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

    /**
     * @param  array<string, mixed>  $enterpriseHealth
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    private function collectEnterpriseHealthWarnings(array $enterpriseHealth, array &$warnings, array &$errors): void
    {
        foreach ($enterpriseHealth as $serviceKey => $serviceHealth) {
            if (! is_array($serviceHealth)) {
                continue;
            }

            $this->collectServiceHealthWarnings('enterprise.'.$serviceKey, $serviceHealth, $warnings, $errors);

            if (isset($serviceHealth['runtime']) && is_array($serviceHealth['runtime'])) {
                $this->collectServiceHealthWarnings(
                    'enterprise.'.$serviceKey.'.runtime',
                    $serviceHealth['runtime'],
                    $warnings,
                    $errors,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $serviceHealth
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    private function collectServiceHealthWarnings(
        string $prefix,
        array $serviceHealth,
        array &$warnings,
        array &$errors,
    ): void {
        if (isset($serviceHealth['missing_tables']) && is_array($serviceHealth['missing_tables'])) {
            foreach ($serviceHealth['warnings'] ?? [] as $warning) {
                $warnings[] = sprintf('[%s] %s', $prefix, $warning);
            }
        }

        if (($serviceHealth['status'] ?? '') === 'critical' && ! isset($serviceHealth['missing_tables'])) {
            $errors[] = sprintf('[%s] Service reported critical health.', $prefix);
        }
    }
}
