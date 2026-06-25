<?php

namespace App\Services\Module;

use App\Models\Application;
use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\ModuleRegistry;
use App\Services\Module\Data\ModuleHealthAggregateReport;
use App\Services\Module\Data\PlatformModuleHealthContext;

class ModuleHealthAggregator
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleValidationService $validationService,
        private readonly ModuleDependencyGraphService $dependencyGraphService,
    ) {
    }

    public function aggregate(): ModuleHealthAggregateReport
    {
        $context = new PlatformModuleHealthContext;
        $moduleHealth = [];
        $healthyCount = 0;
        $warningCount = 0;
        $criticalCount = 0;

        foreach ($this->registry->all() as $module) {
            $report = $module->health($context);
            $moduleHealth[$module->key()] = [
                'status' => $report->status,
                'message' => $report->message,
            ];

            match ($report->status) {
                'critical' => $criticalCount++,
                'warning' => $warningCount++,
                default => $healthyCount++,
            };
        }

        $validation = $this->validationService->validate();
        $graph = $this->dependencyGraphService->build();
        $syncHealth = $this->assessSyncHealth();

        $manifestStatus = $validation->isValid() ? 'healthy' : 'critical';
        $dependencyStatus = $graph->cycles === [] ? 'healthy' : 'critical';
        $runtimeContributors = count(array_filter(
            $this->registry->all(),
            fn (ApplicationModule $module) => $this->supportsRuntimeContribution($module),
        ));

        $overallStatus = 'healthy';

        if ($criticalCount > 0 || ! $validation->isValid() || $graph->cycles !== []) {
            $overallStatus = 'critical';
        } elseif ($warningCount > 0 || $syncHealth['status'] === 'warning') {
            $overallStatus = 'warning';
        }

        return new ModuleHealthAggregateReport(
            overallStatus: $overallStatus,
            moduleHealth: $moduleHealth,
            manifestHealth: [
                'status' => $manifestStatus,
                'issue_count' => count($validation->issues),
            ],
            dependencyHealth: [
                'status' => $dependencyStatus,
                'cycle_count' => count($graph->cycles),
            ],
            syncHealth: $syncHealth,
            runtimeHealth: [
                'status' => 'healthy',
                'contributors' => $runtimeContributors,
            ],
        );
    }

    /**
     * @return array{status: string, synced: int, missing: int, drift: int}
     */
    private function assessSyncHealth(): array
    {
        $modules = $this->registry->all();
        $synced = 0;
        $missing = 0;

        foreach ($modules as $module) {
            $application = Application::query()->where('key', $module->key())->first();

            if ($application === null) {
                $missing++;

                continue;
            }

            if ($application->module_uuid !== $module->manifest()->moduleUuid) {
                $missing++;

                continue;
            }

            $synced++;
        }

        $status = match (true) {
            $missing > 0 => 'warning',
            default => 'healthy',
        };

        return [
            'status' => $status,
            'synced' => $synced,
            'missing' => $missing,
            'drift' => 0,
        ];
    }

    private function supportsRuntimeContribution(ApplicationModule $module): bool
    {
        $method = new \ReflectionMethod($module, 'contributeRuntime');

        return $method->getDeclaringClass()->getName() !== AbstractApplicationModule::class;
    }
}
