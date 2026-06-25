<?php

namespace App\Services\Runtime\Data;

use App\Enums\RuntimeHealthStatus;

readonly class WorkspaceRuntimeDiagnostics
{
    /**
     * @param  list<string>  $dependencyErrors
     * @param  list<string>  $configurationErrors
     * @param  list<string>  $warnings
     * @param  list<string>  $recommendations
     * @param  array<string, mixed>|null  $moduleContributions
     */
    public function __construct(
        public RuntimeHealthStatus $healthStatus,
        public string $runtimeVersion,
        public int $settingsVersion,
        public string $cacheStatus,
        public int $cacheGeneration,
        public bool $cacheHitPossible,
        public array $dependencyErrors,
        public array $configurationErrors,
        public array $warnings,
        public array $recommendations,
        public RuntimePerformanceMetrics $performance,
        public RuntimeCacheDiagnostics $cache,
        public ?array $moduleContributions = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'health_status' => $this->healthStatus->value,
            'runtime_version' => $this->runtimeVersion,
            'settings_version' => $this->settingsVersion,
            'cache_status' => $this->cacheStatus,
            'cache_generation' => $this->cacheGeneration,
            'cache_hit_possible' => $this->cacheHitPossible,
            'dependency_errors' => $this->dependencyErrors,
            'configuration_errors' => $this->configurationErrors,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'performance' => $this->performance->toArray(),
            'cache' => $this->cache->toArray(),
            'module_contributions' => $this->moduleContributions,
        ];
    }
}
