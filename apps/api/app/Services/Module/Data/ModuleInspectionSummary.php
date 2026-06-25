<?php

namespace App\Services\Module\Data;

readonly class ModuleInspectionSummary
{
    /**
     * @param  list<ModuleInspectionResult>  $modules
     */
    public function __construct(
        public int $moduleCount,
        public int $healthyCount,
        public int $warningCount,
        public int $criticalCount,
        public int $runtimeContributorCount,
        public int $lifecycleSupportedCount,
        public array $modules,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_count' => $this->moduleCount,
            'healthy_count' => $this->healthyCount,
            'warning_count' => $this->warningCount,
            'critical_count' => $this->criticalCount,
            'runtime_contributor_count' => $this->runtimeContributorCount,
            'lifecycle_supported_count' => $this->lifecycleSupportedCount,
            'modules' => array_map(
                fn (ModuleInspectionResult $module) => $module->toArray(),
                $this->modules,
            ),
        ];
    }
}
