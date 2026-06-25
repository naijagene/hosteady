<?php

namespace App\Services\Module\Data;

use App\Modules\Sdk\Data\ModuleValidationReport;

readonly class ModuleDoctorReport
{
    /**
     * @param  array<string, mixed>  $platformSummary
     * @param  list<array<string, mixed>>  $modules
     * @param  array<string, mixed>  $dependencyGraph
     * @param  array<string, bool>  $lifecycleSupport
     * @param  array<string, bool>  $runtimeContributionSupport
     * @param  array<string, mixed>  $syncSupport
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public array $platformSummary,
        public array $modules,
        public ModuleValidationReport $validation,
        public array $dependencyGraph,
        public array $lifecycleSupport,
        public array $runtimeContributionSupport,
        public array $syncSupport,
        public ModuleHealthAggregateReport $health,
        public array $warnings,
        public array $errors,
        public array $recommendations,
        public int $exitCode,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'platform_summary' => $this->platformSummary,
            'modules' => $this->modules,
            'validation' => [
                'valid' => $this->validation->isValid(),
                'issues' => array_map(
                    fn ($issue) => [
                        'code' => $issue->code,
                        'message' => $issue->message,
                        'module_key' => $issue->moduleKey,
                    ],
                    $this->validation->issues,
                ),
            ],
            'dependency_graph' => $this->dependencyGraph,
            'lifecycle_support' => $this->lifecycleSupport,
            'runtime_contribution_support' => $this->runtimeContributionSupport,
            'sync_support' => $this->syncSupport,
            'health' => $this->health->toArray(),
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'recommendations' => $this->recommendations,
            'exit_code' => $this->exitCode,
        ];
    }
}
