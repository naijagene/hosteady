<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class TenantAuthorizationServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_owner_receives_all_permissions(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'auth-owner-org']);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $context = TenantContext::fromModels($user, $organization, $membership, $workspace);
        $service = app(TenantAuthorizationService::class);

        $this->assertCount(44, $service->permissionsFor($context));
        $this->assertTrue($service->allows($context, 'organization.archive'));
        $this->assertTrue($service->allows($context, 'workspace.applications.manage'));
    }

    public function test_member_receives_limited_permissions(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'auth-member-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $member = $this->createActiveUser();
        $memberRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $membership = $organization->memberships()->create([
            'user_id' => $member->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $context = TenantContext::fromModels($member, $organization, $membership, $workspace);
        $service = app(TenantAuthorizationService::class);

        $permissions = $service->permissionsFor($context);

        $this->assertSame([
            'applications.read',
            'approval.decide',
            'approval.read',
            'files.read',
            'files.upload',
            'jobs.read',
            'notifications.read',
            'organization.read',
            'reference.read',
            'search.read',
            'task.read',
            'workflow.read',
            'workflow.runtime.read',
            'workspace.applications.read',
            'workspace.read',
        ], $permissions);
        $this->assertFalse($service->allows($context, 'organization.archive'));
    }
}
