<?php

namespace Tests\Feature\Services\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Audit\DomainAuditRecorder;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class DomainAuditRuntimeTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_records_workspace_runtime_generated_event(): void
    {
        $context = $this->tenantContextWithDemo();

        app(WorkspaceRuntimeProvider::class)->resolve($context);

        $event = AuditLog::query()
            ->where('action', AuditAction::WorkspaceRuntimeGenerated->value)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($context->workspace->id, $event->workspace_id);
    }

    public function test_records_workspace_runtime_failed_event(): void
    {
        $context = $this->tenantContextWithDemo();

        app(DomainAuditRecorder::class)->recordWorkspaceRuntimeFailed($context);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::WorkspaceRuntimeFailed->value,
            'workspace_id' => $context->workspace->id,
        ]);
    }

    public function test_generated_event_includes_runtime_metadata(): void
    {
        $context = $this->tenantContextWithDemo();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        app(DomainAuditRecorder::class)->recordWorkspaceRuntimeGenerated($context, $runtime);

        $event = AuditLog::query()
            ->where('action', AuditAction::WorkspaceRuntimeGenerated->value)
            ->latest('occurred_at')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame($runtime->runtimeVersion, $event->metadata['runtime_version'] ?? null);
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'domain-audit-runtime-org']);
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
