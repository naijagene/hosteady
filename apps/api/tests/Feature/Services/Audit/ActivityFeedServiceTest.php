<?php

namespace Tests\Feature\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditCategory;
use App\Enums\AuditRetentionClass;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Role;
use App\Services\Audit\ActivityFeedService;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ActivityFeedServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private ActivityFeedService $service;

    private AuditEventRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ActivityFeedService::class);
        $this->recorder = app(AuditEventRecorder::class);
    }

    public function test_lists_events_for_organization(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-feed-org']);
        $context = $this->buildTenantContext($user, $result);

        app()->instance(TenantContext::class, $context);

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));

        $events = $this->service->listEvents($context);

        $this->assertSame(1, $events->total());
        $this->assertSame('application.installed', $events->items()[0]->action->value);
    }

    public function test_filters_by_category(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-filter-org']);
        $context = $this->buildTenantContext($user, $result);

        app()->instance(TenantContext::class, $context);

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));
        $this->recorder->record(new AuditEventData(
            action: AuditAction::WorkspaceUpdated,
            summary: 'Workspace updated',
        ));

        $events = $this->service->listEvents($context, [
            'category' => AuditCategory::Application->value,
        ]);

        $this->assertSame(1, $events->total());
    }

    public function test_hides_ephemeral_security_events_from_manager(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'activity-security-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $manager = $this->createActiveUser();
        $managerRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'manager')
            ->firstOrFail();

        $managerMembership = $organization->memberships()->create([
            'user_id' => $manager->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'join_method' => JoinMethod::Invitation,
        ]);
        $managerMembership->memberRoles()->create([
            'role_id' => $managerRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownerContext = $this->buildTenantContext($owner, $result);
        app()->instance(TenantContext::class, $ownerContext);

        $this->recorder->record(new AuditEventData(
            action: AuditAction::SecurityAccessDenied,
            summary: 'Access denied',
        ));
        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));

        $managerContext = TenantContext::fromModels($manager, $organization, $managerMembership, $workspace);
        $events = $this->service->listEvents($managerContext);

        $this->assertSame(1, $events->total());
        $this->assertSame('application.installed', $events->items()[0]->action->value);
    }

    public function test_finds_single_event_by_public_id(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-show-org']);
        $context = $this->buildTenantContext($user, $result);

        app()->instance(TenantContext::class, $context);

        $log = $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));

        $found = $this->service->findEvent($context, $log->public_id);

        $this->assertTrue($found->is($log));
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
