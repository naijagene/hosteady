<?php

namespace Tests\Feature\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditCategory;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Services\Audit\ActivityFeedService;
use App\Services\Audit\AuditCursorCodec;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Http\RequestContext;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_lists_events_for_organization_with_cursor_pagination(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-feed-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->clearOrganizationAuditLogs($result->organizationPublicId);

        app()->instance(TenantContext::class, $context);

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));

        $page = $this->service->listEvents($context);

        $this->assertSame(1, $page->items->count());
        $this->assertFalse($page->usesOffsetPagination);
        $this->assertSame('application.installed', $page->items->first()->action->value);
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

        $page = $this->service->listEvents($context, [
            'category' => [AuditCategory::Application->value],
        ]);

        $this->assertSame(1, $page->items->count());
    }

    public function test_filters_by_request_id(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-request-id-org']);
        $context = $this->buildTenantContext($user, $result);
        $requestId = (string) Str::uuid7();

        app()->instance(TenantContext::class, $context);
        app()->instance(RequestContext::class, new RequestContext(
            requestId: $requestId,
            ipAddress: '203.0.113.10',
            userAgent: 'PHPUnit',
        ));

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));
        $this->recorder->record(new AuditEventData(
            action: AuditAction::WorkspaceUpdated,
            summary: 'Workspace updated',
        ));

        app()->instance(RequestContext::class, new RequestContext(
            requestId: (string) Str::uuid7(),
            ipAddress: '203.0.113.11',
            userAgent: 'PHPUnit',
        ));

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationEnabled,
            summary: 'Demo Application enabled',
        ));

        $page = $this->service->listEvents($context, [
            'request_id' => $requestId,
        ]);

        $this->assertSame(2, $page->items->count());
    }

    public function test_cursor_pagination_returns_next_cursor(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-cursor-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->clearOrganizationAuditLogs($result->organizationPublicId);

        app()->instance(TenantContext::class, $context);

        foreach (['First', 'Second', 'Third'] as $index => $label) {
            $this->recorder->record(new AuditEventData(
                action: AuditAction::ApplicationInstalled,
                summary: $label,
            ));

            if ($index < 2) {
                usleep(1000);
            }
        }

        $firstPage = $this->service->listEvents($context, ['limit' => 2]);
        $this->assertTrue($firstPage->hasMore);
        $this->assertNotNull($firstPage->nextCursor);

        $secondPage = $this->service->listEvents($context, [
            'limit' => 2,
            'cursor' => $firstPage->nextCursor,
        ]);

        $this->assertSame(1, $secondPage->items->count());
        $this->assertFalse($secondPage->hasMore);
    }

    public function test_offset_pagination_when_page_provided(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-offset-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->clearOrganizationAuditLogs($result->organizationPublicId);

        app()->instance(TenantContext::class, $context);

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));

        $page = $this->service->listEvents($context, ['page' => 1]);

        $this->assertTrue($page->usesOffsetPagination);
        $this->assertNotNull($page->offsetPaginator);
        $this->assertSame(1, $page->offsetPaginator->total());
    }

    public function test_summarize_returns_top_actors_and_default_window(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-summary-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->clearOrganizationAuditLogs($result->organizationPublicId);

        app()->instance(TenantContext::class, $context);

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));
        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationEnabled,
            summary: 'Demo Application enabled',
        ));

        $summary = $this->service->summarize($context);

        $this->assertSame(2, $summary->totalEvents);
        $this->assertCount(1, $summary->topActors);
        $this->assertSame($result->membershipPublicId, $summary->topActors[0]['membership_public_id']);
        $this->assertSame(2, $summary->topActors[0]['count']);
    }

    public function test_summarize_filters_by_request_id(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'activity-summary-request-org']);
        $context = $this->buildTenantContext($user, $result);
        $requestId = (string) Str::uuid7();

        app()->instance(TenantContext::class, $context);
        app()->instance(RequestContext::class, new RequestContext(
            requestId: $requestId,
            ipAddress: '203.0.113.10',
            userAgent: 'PHPUnit',
        ));

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
        ));

        app()->instance(RequestContext::class, new RequestContext(
            requestId: (string) Str::uuid7(),
            ipAddress: '203.0.113.11',
            userAgent: 'PHPUnit',
        ));

        $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationEnabled,
            summary: 'Demo Application enabled',
        ));

        $summary = $this->service->summarize($context, [
            'request_id' => $requestId,
        ]);

        $this->assertSame(1, $summary->totalEvents);
    }

    public function test_hides_ephemeral_security_events_from_manager(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'activity-security-org']);
        $organization = $this->findProvisionedOrganization($result);
        $this->clearOrganizationAuditLogs($result->organizationPublicId);
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
        $page = $this->service->listEvents($managerContext);

        $this->assertSame(1, $page->items->count());
        $this->assertSame('application.installed', $page->items->first()->action->value);
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

    private function clearOrganizationAuditLogs(string $organizationPublicId): void
    {
        $organization = \App\Models\Organization::query()
            ->where('public_id', $organizationPublicId)
            ->firstOrFail();

        AuditLog::query()
            ->where('organization_id', $organization->id)
            ->delete();
    }
}
