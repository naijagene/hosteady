<?php

namespace App\Services\Module\Data;

readonly class ModuleHealthAggregateReport
{
    /**
     * @param  array<string, array{status: string, message: ?string}>  $moduleHealth
     * @param  array{status: string, issue_count: int}  $manifestHealth
     * @param  array{status: string, cycle_count: int}  $dependencyHealth
     * @param  array{status: string, synced: int, missing: int, drift: int}  $syncHealth
     * @param  array{status: string, contributors: int}  $runtimeHealth
     */
    public function __construct(
        public string $overallStatus,
        public array $moduleHealth,
        public array $manifestHealth,
        public array $dependencyHealth,
        public array $syncHealth,
        public array $runtimeHealth,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'overall_status' => $this->overallStatus,
            'modules' => $this->moduleHealth,
            'manifest' => $this->manifestHealth,
            'dependency' => $this->dependencyHealth,
            'sync' => $this->syncHealth,
            'runtime' => $this->runtimeHealth,
        ];
    }
}
