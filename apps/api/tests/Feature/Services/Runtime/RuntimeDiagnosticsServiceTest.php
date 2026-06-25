<?php

namespace Tests\Feature\Services\Runtime;

use App\Enums\RuntimeHealthStatus;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Runtime\RuntimeDiagnosticsService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeDiagnosticsServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_diagnose_returns_diagnostics_dto(): void
    {
        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($this->tenantContextWithDemo());

        $this->assertNotEmpty($diagnostics->runtimeVersion);
        $this->assertSame('disabled', $diagnostics->cacheStatus);
        $this->assertInstanceOf(RuntimeHealthStatus::class, $diagnostics->healthStatus);
    }

    public function test_diagnose_includes_cache_generation(): void
    {
        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($this->tenantContext());

        $this->assertGreaterThanOrEqual(1, $diagnostics->cacheGeneration);
    }

    public function test_diagnose_reports_dependency_errors_when_missing(): void
    {
        $context = $this->tenantContextWithDemo();
        Application::query()->where('key', 'demo')->update(['dependencies' => ['missing-app']]);

        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($context);

        $this->assertNotEmpty($diagnostics->dependencyErrors);
    }

    public function test_diagnose_includes_recommendations_when_cache_disabled(): void
    {
        config(['heos.runtime_cache.enabled' => false]);

        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($this->tenantContext());

        $this->assertContains('Enable runtime cache in production for lower latency.', $diagnostics->recommendations);
    }

    public function test_diagnose_populates_setting_count_estimate(): void
    {
        $context = $this->tenantContextWithDemo();
        app(\App\Services\WorkspaceApplication\WorkspaceRuntimeProvider::class)->resolve($context);

        $diagnostics = app(RuntimeDiagnosticsService::class)->diagnose($context);

        $this->assertGreaterThanOrEqual(0, $diagnostics->performance->settingCount);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-diagnostics-'.uniqid()]);
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
