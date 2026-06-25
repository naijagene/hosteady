<?php

namespace Tests\Feature\Services\Runtime;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Audit\DomainAuditRecorder;
use App\Services\Runtime\AuditedWorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class AuditedWorkspaceRuntimeProviderTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_records_generated_audit_on_successful_resolve(): void
    {
        $context = $this->tenantContextWithDemo();

        app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::WorkspaceRuntimeGenerated->value,
            'workspace_id' => $context->workspace->id,
        ]);
    }

    public function test_records_failed_audit_on_runtime_generation_failure(): void
    {
        $context = $this->tenantContextWithDemo();
        $recorder = Mockery::mock(DomainAuditRecorder::class);
        $recorder->shouldReceive('recordWorkspaceRuntimeFailed')->once()->with($context);
        $inner = Mockery::mock(WorkspaceRuntimeProvider::class);
        $inner->shouldReceive('resolve')->once()->andThrow(new \RuntimeException('boom'));

        $provider = new AuditedWorkspaceRuntimeProvider(
            $inner,
            $recorder,
            app(\App\Services\Runtime\RuntimeMetricsCollector::class),
        );

        $this->expectException(\App\Exceptions\WorkspaceApplication\RuntimeUnavailableException::class);

        $provider->resolve($context);
    }

    public function test_audit_failure_does_not_break_runtime_generation(): void
    {
        $context = $this->tenantContextWithDemo();
        $recorder = Mockery::mock(DomainAuditRecorder::class);
        $recorder->shouldReceive('recordWorkspaceRuntimeGenerated')->once()->andThrow(new \RuntimeException('audit down'));
        $inner = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeResolver::class);

        $runtime = (new AuditedWorkspaceRuntimeProvider(
            $inner,
            $recorder,
            app(\App\Services\Runtime\RuntimeMetricsCollector::class),
        ))->resolve($context);

        $this->assertNotEmpty($runtime->runtimeVersion);
    }

    public function test_preserves_workspace_application_not_found_exception(): void
    {
        $context = $this->tenantContextWithDemo();

        $this->expectException(\App\Exceptions\WorkspaceApplication\WorkspaceApplicationNotFoundException::class);

        app(WorkspaceRuntimeProvider::class)->resolve($context, '01999999-9999-7999-8999-999999999999');
    }

    public function test_failed_audit_is_best_effort_via_domain_recorder(): void
    {
        $context = $this->tenantContextWithDemo();
        $before = AuditLog::query()->count();

        app(DomainAuditRecorder::class)->recordWorkspaceRuntimeFailed($context);

        $this->assertSame($before + 1, AuditLog::query()->count());
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audited-runtime-org']);
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
