<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\AuditAction;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Runtime\AuditedWorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceRuntimeHealthTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_runtime_health_payload(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-health-api-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime/health');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'health',
                    'diagnostics' => [
                        'health_status',
                        'runtime_version',
                        'settings_version',
                        'cache_status',
                        'cache_generation',
                        'cache_hit_possible',
                        'dependency_errors',
                        'configuration_errors',
                        'warnings',
                        'recommendations',
                        'performance',
                        'cache',
                    ],
                    'integrity' => ['status', 'fingerprint_valid', 'errors', 'warnings'],
                    'cache' => ['enabled', 'generation', 'ttl', 'hit_possible', 'backend', 'schema_version'],
                    'dependency_summary',
                    'recommendations',
                ],
            ]);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_health_endpoint_requires_authentication(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-health-auth-org']);

        $this->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime/health')
            ->assertUnauthorized();
    }

    public function test_health_endpoint_requires_organization_header(): void
    {
        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/tenant/workspace/runtime/health')
            ->assertStatus(422);
    }

    public function test_runtime_endpoint_returns503_when_generation_fails(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-unavailable-org']);
        $token = $this->issueToken($user);
        $context = $this->buildTenantContext($user, $result);

        $inner = Mockery::mock(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class);
        $inner->shouldReceive('resolveSummary')->andReturn(new \App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary(2, 'hash', 1));
        $inner->shouldReceive('resolve')->andThrow(new \RuntimeException('generation failed'));

        $this->app->instance(WorkspaceRuntimeProvider::class, new AuditedWorkspaceRuntimeProvider(
            $inner,
            app(\App\Services\Audit\DomainAuditRecorder::class),
            app(\App\Services\Runtime\RuntimeMetricsCollector::class),
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Runtime unavailable.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::WorkspaceRuntimeFailed->value,
            'workspace_id' => $context->workspace->id,
        ]);
    }

    public function test_health_reports_demo_dependency_summary(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-health-demo-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime/health')
            ->assertOk()
            ->assertJsonPath('data.integrity.fingerprint_valid', true);
    }

    public function test_health_levels_can_be_warning_when_cache_disabled(): void
    {
        config(['heos.runtime_cache.enabled' => false]);

        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-health-cache-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime/health')
            ->assertOk();

        $this->assertContains($response->json('data.diagnostics.cache_status'), ['disabled', 'miss', 'hit_possible']);
    }

    public function test_runtime_api_regression_still_returns_runtime_payload(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-health-regression-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk()
            ->assertJsonCount(3, 'data.active_applications')
            ->assertJsonPath('data.runtime_metadata.generated_by', 'WorkspaceRuntimeResolver');
    }

    private function enableDemoApplication(TenantContext $context): void
    {
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $installation = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        app(WorkspaceApplicationService::class)->enable($context, $installation->public_id);
    }

    private function buildTenantContext(
        \App\Models\User $user,
        \App\Services\Organization\Data\ProvisionedOrganizationResult $result,
    ): TenantContext {
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
