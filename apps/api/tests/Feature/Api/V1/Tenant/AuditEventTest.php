<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\AuditAction;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Role;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_lists_audit_events_for_owner(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-api-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'public_id',
                        'occurred_at',
                        'category',
                        'action',
                        'event_version',
                        'severity',
                        'summary',
                        'actor',
                        'entity',
                        'changes',
                        'context',
                    ],
                ],
                'meta',
                'links',
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        $this->assertSame('tenant.context.selected', $response->json('data.0.action'));
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_returns_request_id_header(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-request-id-org']);
        $token = $this->issueToken($user);
        $requestId = (string) Str::uuid7();

        $response = $this->withBearerToken($token)
            ->withHeader('X-Request-Id', $requestId)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events');

        $response->assertOk()
            ->assertHeader('X-Request-Id', $requestId);
    }

    public function test_shows_single_audit_event(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-show-org']);
        $organization = $this->findProvisionedOrganization($result);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context');

        $eventPublicId = AuditLog::query()
            ->where('organization_id', $organization->id)
            ->where('action', AuditAction::TenantContextSelected)
            ->value('public_id');

        $this->assertNotNull($eventPublicId);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson("/api/v1/tenant/audit/events/{$eventPublicId}");

        $response->assertOk()
            ->assertJsonPath('data.action', 'tenant.context.selected')
            ->assertJsonPath('data.event_version', 1);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_denies_access_without_audit_read_permission(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'audit-deny-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $member = $this->createActiveUser();
        $memberRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $organization->memberships()->create([
            'user_id' => $member->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'join_method' => JoinMethod::Invitation,
        ])->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->issueToken($member);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events')
            ->assertForbidden();
    }

    public function test_returns_not_found_for_unknown_event(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-missing-org']);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events/01999999-9999-7999-8999-999999999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Audit event not found.');
    }

    public function test_filters_by_action(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-filter-org']);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events?action=tenant.context.selected')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
