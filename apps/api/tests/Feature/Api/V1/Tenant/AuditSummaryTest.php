<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Role;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class AuditSummaryTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_returns_summary_for_owner(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-summary-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'occurred_from',
                    'occurred_to',
                    'total_events',
                    'by_category',
                    'by_severity',
                    'recent_actions',
                    'top_actors',
                ],
            ])
            ->assertJsonPath('data.total_events', 1)
            ->assertJsonCount(1, 'data.top_actors');

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_summary_filters_by_request_id(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-summary-request-org']);
        $token = $this->issueToken($user);
        $requestId = (string) Str::uuid7();

        $this->withBearerToken($token)
            ->withHeader('X-Request-Id', $requestId)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertOk();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            ResolveTenantContext::ORGANIZATION_HEADER => $result->organizationPublicId,
            AssignRequestId::HEADER => (string) Str::uuid7(),
        ])->getJson('/api/v1/tenant/audit/summary?request_id='.$requestId)
            ->assertOk()
            ->assertJsonPath('data.total_events', 1);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            ResolveTenantContext::ORGANIZATION_HEADER => $result->organizationPublicId,
            AssignRequestId::HEADER => (string) Str::uuid7(),
        ])->getJson('/api/v1/tenant/audit/summary?request_id='.(string) Str::uuid7())
            ->assertOk()
            ->assertJsonPath('data.total_events', 0);
    }

    public function test_denies_summary_without_audit_read_permission(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'audit-summary-deny-org']);
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
            ->getJson('/api/v1/tenant/audit/summary')
            ->assertForbidden();
    }
}
