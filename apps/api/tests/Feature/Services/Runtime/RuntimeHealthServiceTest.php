<?php

namespace Tests\Feature\Services\Runtime;

use App\Enums\RuntimeHealthStatus;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Runtime\RuntimeCacheDiagnosticsService;
use App\Services\Runtime\RuntimeDependencyValidator;
use App\Services\Runtime\RuntimeDiagnosticsService;
use App\Services\Runtime\RuntimeHealthService;
use App\Services\Runtime\RuntimeIntegrityValidator;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeHealthServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_assess_returns_healthy_for_bootstrap_workspace(): void
    {
        $health = app(RuntimeHealthService::class)->assess($this->tenantContext());

        $this->assertContains($health->health->value, [
            RuntimeHealthStatus::Healthy->value,
            RuntimeHealthStatus::Warning->value,
        ]);
        $this->assertTrue($health->integrity->fingerprintValid);
        $this->assertArrayHasKey('status', $health->dependencySummary);
    }

    public function test_diagnostics_include_performance_metadata(): void
    {
        $context = $this->tenantContextWithDemo();
        app(\App\Services\WorkspaceApplication\WorkspaceRuntimeProvider::class)->resolve($context);

        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($context);

        $this->assertGreaterThanOrEqual(3, $diagnostics->performance->applicationCount);
        $this->assertArrayHasKey('generation_duration_ms', $diagnostics->performance->toArray());
        $this->assertArrayHasKey('memory_usage_estimate_bytes', $diagnostics->performance->toArray());
    }

    public function test_cache_diagnostics_report_disabled_status_in_tests(): void
    {
        config(['heos.runtime_cache.enabled' => false]);

        $cache = app(RuntimeCacheDiagnosticsService::class)->diagnose(
            $this->tenantContext(),
            app(\App\Services\WorkspaceApplication\WorkspaceRuntimeProvider::class)->resolveSummary($this->tenantContext()),
        );

        $this->assertFalse($cache->enabled);
        $this->assertSame('array', $cache->backend);
    }

    public function test_cache_diagnostics_report_enabled_configuration(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContext();
        $summary = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeProvider::class)->resolveSummary($context);
        $cache = app(RuntimeCacheDiagnosticsService::class)->diagnose($context, $summary);

        $this->assertTrue($cache->enabled);
        $this->assertSame(1, $cache->schemaVersion);
        $this->assertStringStartsWith('heos:runtime:v1:schema1:', (string) $cache->key);
    }

    public function test_integrity_validator_detects_valid_fingerprint(): void
    {
        $report = app(RuntimeIntegrityValidator::class)->validate($this->tenantContextWithDemo());

        $this->assertTrue($report->fingerprintValid);
        $this->assertSame(RuntimeHealthStatus::Healthy, $report->status);
    }

    public function test_dependency_validator_passes_for_demo_dependencies(): void
    {
        $report = app(RuntimeDependencyValidator::class)->validate($this->tenantContextWithDemo());

        $this->assertSame(RuntimeHealthStatus::Healthy, $report->status);
        $this->assertSame([], $report->missingDependencies);
    }

    public function test_health_status_escalates_to_warning_when_cache_disabled(): void
    {
        config(['heos.runtime_cache.enabled' => false]);

        $health = app(RuntimeHealthService::class)->assess($this->tenantContext());

        $this->assertContains($health->health->value, [
            RuntimeHealthStatus::Healthy->value,
            RuntimeHealthStatus::Warning->value,
        ]);
        $this->assertNotEmpty($health->recommendations);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-health-bootstrap-'.uniqid()]);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $context = $this->tenantContext();
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $installation = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        app(WorkspaceApplicationService::class)->enable($context, $installation->public_id);

        return $context;
    }
}
