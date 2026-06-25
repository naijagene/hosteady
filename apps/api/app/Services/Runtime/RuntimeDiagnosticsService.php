<?php

namespace App\Services\Runtime;

use App\Enums\RuntimeHealthStatus;
use App\Modules\Sdk\Runtime\RuntimeExtensionManager;
use App\Services\Runtime\Data\RuntimeDependencyReport;
use App\Services\Runtime\Data\RuntimeIntegrityReport;
use App\Services\Runtime\Data\WorkspaceRuntimeDiagnostics;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;

class RuntimeDiagnosticsService
{
    public function __construct(
        private readonly WorkspaceRuntimeProvider $runtimeProvider,
        private readonly RuntimeDependencyValidator $dependencyValidator,
        private readonly RuntimeIntegrityValidator $integrityValidator,
        private readonly RuntimeCacheDiagnosticsService $cacheDiagnosticsService,
        private readonly RuntimeExtensionManager $runtimeExtensionManager,
    ) {
    }

    public function diagnose(
        TenantContext $context,
        ?string $activeWorkspaceApplicationPublicId = null,
    ): WorkspaceRuntimeDiagnostics {
        $summary = $this->runtimeProvider->resolveSummary($context);
        $moduleContributions = $this->runtimeExtensionManager->lastPipelineReport()?->toDiagnosticsSummary();
        $cache = $this->cacheDiagnosticsService->diagnose($context, $summary, $activeWorkspaceApplicationPublicId);
        $integrity = $this->integrityValidator->validate($context);
        $dependency = $this->dependencyValidator->validate($context);

        $configurationErrors = $integrity->errors;
        $dependencyErrors = $dependency->errors;
        $warnings = array_values(array_unique(array_merge(
            $integrity->warnings,
            $dependency->warnings,
            $moduleContributions['warnings'] ?? [],
        )));
        $recommendations = $this->buildRecommendations($cache, $integrity, $dependency);

        $healthStatus = RuntimeHealthStatus::worst(
            $integrity->status,
            $dependency->status,
            $this->cacheHealthStatus($cache),
        );

        $performance = $this->cacheDiagnosticsService->performanceFromSummary($summary, $cache);
        $settingCount = $this->estimateSettingCount($context);

        if ($performance->settingCount === 0 && $settingCount > 0) {
            $performance = new \App\Services\Runtime\Data\RuntimePerformanceMetrics(
                generationDurationMs: $performance->generationDurationMs,
                applicationCount: $performance->applicationCount,
                settingCount: $settingCount,
                memoryUsageEstimateBytes: ($performance->applicationCount * 4096) + ($settingCount * 1024) + 8192,
                cacheHitPossible: $performance->cacheHitPossible,
            );
        }

        return new WorkspaceRuntimeDiagnostics(
            healthStatus: $healthStatus,
            runtimeVersion: $summary->runtimeVersion,
            settingsVersion: $summary->settingsVersion,
            cacheStatus: $this->cacheDiagnosticsService->cacheStatus($cache),
            cacheGeneration: $cache->generation,
            cacheHitPossible: $cache->hitPossible,
            dependencyErrors: $dependencyErrors,
            configurationErrors: $configurationErrors,
            warnings: $warnings,
            recommendations: $recommendations,
            performance: $performance,
            cache: $cache,
            moduleContributions: $moduleContributions,
        );
    }

    private function cacheHealthStatus(\App\Services\Runtime\Data\RuntimeCacheDiagnostics $cache): RuntimeHealthStatus
    {
        if (! $cache->enabled) {
            return RuntimeHealthStatus::Warning;
        }

        return RuntimeHealthStatus::Healthy;
    }

    /**
     * @return list<string>
     */
    private function buildRecommendations(
        \App\Services\Runtime\Data\RuntimeCacheDiagnostics $cache,
        RuntimeIntegrityReport $integrity,
        RuntimeDependencyReport $dependency,
    ): array {
        $recommendations = [];

        if (! $cache->enabled) {
            $recommendations[] = 'Enable runtime cache in production for lower latency.';
        }

        if (! $integrity->fingerprintValid) {
            $recommendations[] = 'Investigate runtime fingerprint drift and invalidate workspace runtime cache.';
        }

        if ($dependency->missingDependencies !== []) {
            $recommendations[] = 'Enable missing application dependencies required by active workspace applications.';
        }

        if ($dependency->circularDependencies !== []) {
            $recommendations[] = 'Resolve circular application dependencies in the catalog manifest.';
        }

        if ($integrity->warnings !== []) {
            $recommendations[] = 'Review orphan workspace settings and inactive definitions.';
        }

        return $recommendations;
    }

    private function estimateSettingCount(TenantContext $context): int
    {
        return \App\Models\WorkspaceApplicationSetting::query()
            ->whereNull('deleted_at')
            ->whereIn('workspace_application_id', function ($query) use ($context) {
                $query->select('id')
                    ->from('workspace_applications')
                    ->where('workspace_id', $context->workspace->id)
                    ->where('organization_id', $context->organization->id)
                    ->whereNull('deleted_at');
            })
            ->count();
    }
}
