<?php

namespace App\Services\Runtime;

use App\Services\Runtime\Data\RuntimePerformanceMetrics;

class RuntimeMetricsCollector
{
    private ?RuntimePerformanceMetrics $lastMetrics = null;

    private bool $lastCacheHitPossible = false;

    public function record(
        float $generationDurationMs,
        int $applicationCount,
        int $settingCount,
        bool $cacheHitPossible,
    ): void {
        $this->lastCacheHitPossible = $cacheHitPossible;
        $this->lastMetrics = new RuntimePerformanceMetrics(
            generationDurationMs: $generationDurationMs,
            applicationCount: $applicationCount,
            settingCount: $settingCount,
            memoryUsageEstimateBytes: $this->estimateMemoryUsage($applicationCount, $settingCount),
            cacheHitPossible: $cacheHitPossible,
        );
    }

    public function lastMetrics(): ?RuntimePerformanceMetrics
    {
        return $this->lastMetrics;
    }

    public function lastCacheHitPossible(): bool
    {
        return $this->lastCacheHitPossible;
    }

    public function estimate(int $applicationCount, int $settingCount, bool $cacheHitPossible): RuntimePerformanceMetrics
    {
        return new RuntimePerformanceMetrics(
            generationDurationMs: 0.0,
            applicationCount: $applicationCount,
            settingCount: $settingCount,
            memoryUsageEstimateBytes: $this->estimateMemoryUsage($applicationCount, $settingCount),
            cacheHitPossible: $cacheHitPossible,
        );
    }

    private function estimateMemoryUsage(int $applicationCount, int $settingCount): int
    {
        return ($applicationCount * 4096) + ($settingCount * 1024) + 8192;
    }
}
