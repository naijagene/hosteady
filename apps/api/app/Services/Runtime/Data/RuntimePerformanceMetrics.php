<?php

namespace App\Services\Runtime\Data;

readonly class RuntimePerformanceMetrics
{
    public function __construct(
        public float $generationDurationMs,
        public int $applicationCount,
        public int $settingCount,
        public int $memoryUsageEstimateBytes,
        public bool $cacheHitPossible,
    ) {
    }

    /**
     * @return array<string, int|float|bool>
     */
    public function toArray(): array
    {
        return [
            'generation_duration_ms' => $this->generationDurationMs,
            'application_count' => $this->applicationCount,
            'setting_count' => $this->settingCount,
            'memory_usage_estimate_bytes' => $this->memoryUsageEstimateBytes,
            'cache_hit_possible' => $this->cacheHitPossible,
        ];
    }
}
