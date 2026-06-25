<?php

namespace Tests\Feature\Services\Runtime;

use App\Enums\RuntimeHealthStatus;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Runtime\RuntimeDependencyValidator;
use App\Services\Runtime\RuntimeIntegrityValidator;
use App\Services\Runtime\RuntimeMetricsCollector;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeProductionHardeningTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_metrics_collector_records_generation_metadata(): void
    {
        $context = $this->tenantContextWithDemo();

        app(WorkspaceRuntimeProvider::class)->resolve($context);

        $metrics = app(RuntimeMetricsCollector::class)->lastMetrics();

        $this->assertNotNull($metrics);
        $this->assertGreaterThanOrEqual(3, $metrics->applicationCount);
        $this->assertGreaterThanOrEqual(0, $metrics->generationDurationMs);
    }

    public function test_health_service_merges_integrity_and_dependency_status(): void
    {
        $context = $this->tenantContextWithDemo();
        Application::query()->where('key', 'demo')->update(['dependencies' => ['missing-app']]);

        $health = app(\App\Services\Runtime\RuntimeHealthService::class)->assess($context);

        $this->assertNotSame(RuntimeHealthStatus::Healthy, $health->health);
        $this->assertNotEmpty($health->diagnostics->dependencyErrors);
    }

    public function test_integrity_validator_reports_configuration_errors_array(): void
    {
        $report = app(RuntimeIntegrityValidator::class)->validate($this->tenantContextWithDemo());

        $this->assertIsArray($report->errors);
        $this->assertIsArray($report->warnings);
    }

    public function test_dependency_summary_exposes_counts(): void
    {
        $summary = app(RuntimeDependencyValidator::class)->validate($this->tenantContextWithDemo())->summary();

        $this->assertArrayHasKey('missing_count', $summary);
        $this->assertArrayHasKey('circular_count', $summary);
        $this->assertSame('healthy', $summary['status']);
    }

    public function test_runtime_health_dto_serializes_without_internal_cache_values(): void
    {
        $health = app(\App\Services\Runtime\RuntimeHealthService::class)->assess($this->tenantContextWithDemo());
        $payload = $health->toArray();

        $this->assertArrayHasKey('health', $payload);
        $this->assertArrayNotHasKey('value', $payload['cache']);
    }

    public function test_diagnostics_health_status_reflects_disabled_cache(): void
    {
        config(['heos.runtime_cache.enabled' => false]);

        $diagnostics = app(\App\Services\Runtime\RuntimeDiagnosticsService::class)
            ->diagnose($this->tenantContextWithDemo());

        $this->assertSame('disabled', $diagnostics->cacheStatus);
        $this->assertFalse($diagnostics->cache->enabled);
    }

    public function test_resolve_summary_succeeds_under_audit_wrapper(): void
    {
        $summary = app(WorkspaceRuntimeProvider::class)->resolveSummary($this->tenantContextWithDemo());

        $this->assertGreaterThanOrEqual(3, $summary->activeApplicationCount);
        $this->assertNotEmpty($summary->runtimeVersion);
    }

    public function test_dependency_validator_reserves_version_mismatch_bucket(): void
    {
        $report = app(RuntimeDependencyValidator::class)->validate($this->tenantContextWithDemo());

        $this->assertSame([], $report->versionMismatches);
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-hardening-'.uniqid()]);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $context = TenantContext::fromModels($user, $organization, $membership, $workspace);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $installation = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        app(WorkspaceApplicationService::class)->enable($context, $installation->public_id);

        return $context;
    }
}
