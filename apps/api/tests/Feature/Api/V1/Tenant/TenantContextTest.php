<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\OrganizationStatus;
use App\Enums\WorkspaceStatus;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_returns_resolved_tenant_context(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'tenant-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context');

        $response->assertOk()
            ->assertJsonPath('data.user.public_id', $user->public_id)
            ->assertJsonPath('data.organization.public_id', $result->organizationPublicId)
            ->assertJsonPath('data.membership.public_id', $result->membershipPublicId)
            ->assertJsonPath('data.workspace.public_id', $result->workspacePublicId)
            ->assertJsonStructure([
                'data' => [
                    'permissions',
                ],
            ]);

        $this->assertContains('organization.read', $response->json('data.permissions'));
        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_requires_organization_header(): void
    {
        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/tenant/context')
            ->assertStatus(422)
            ->assertJsonPath('message', 'The X-HEOS-Organization-Id header is required.');
    }

    public function test_rejects_non_member(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'private-org']);

        $outsider = $this->createActiveUser();
        $token = $this->issueToken($outsider);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertForbidden()
            ->assertJsonPath('message', 'You do not have an active membership for this organization.');
    }

    public function test_returns_not_found_for_unknown_organization(): void
    {
        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders('01999999-9999-7999-8999-999999999999')
            ->getJson('/api/v1/tenant/context')
            ->assertNotFound()
            ->assertJsonPath('message', 'Organization not found.');
    }

    public function test_uses_membership_default_workspace_when_header_omitted(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'default-workspace-org']);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertOk()
            ->assertJsonPath('data.workspace.slug', 'default')
            ->assertJsonPath('data.workspace.is_default', true);
    }

    public function test_accepts_workspace_header_override(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'workspace-override-org']);
        $organization = $this->findProvisionedOrganization($result);

        $secondaryWorkspace = Workspace::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Secondary',
            'slug' => 'secondary',
            'is_default' => false,
            'status' => WorkspaceStatus::Active,
        ]);

        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId, $secondaryWorkspace->public_id)
            ->getJson('/api/v1/tenant/context')
            ->assertOk()
            ->assertJsonPath('data.workspace.public_id', $secondaryWorkspace->public_id)
            ->assertJsonPath('data.workspace.slug', 'secondary');
    }

    public function test_denies_access_without_organization_read_permission(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'policy-org']);
        $organization = $this->findProvisionedOrganization($result);

        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $ownerRole = Role::query()->where('organization_id', $organization->id)->where('key', 'owner')->firstOrFail();

        DB::table('role_permissions')
            ->where('role_id', $ownerRole->id)
            ->whereIn('permission_id', function ($query) {
                $query->select('id')
                    ->from('permissions')
                    ->where('key', 'organization.read');
            })
            ->delete();

        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertForbidden();
    }

    public function test_rejects_inactive_organization(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'inactive-org']);
        $organization = $this->findProvisionedOrganization($result);
        $organization->status = OrganizationStatus::Suspended;
        $organization->save();

        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertForbidden()
            ->assertJsonPath('message', 'Organization is not active.');
    }
}
