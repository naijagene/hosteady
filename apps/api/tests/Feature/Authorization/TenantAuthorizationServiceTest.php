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

        $this->assertCount(134, $service->permissionsFor($context));
        $this->assertTrue($service->allows($context, 'organization.archive'));
        $this->assertTrue($service->allows($context, 'platform.read'));
        $this->assertTrue($service->allows($context, 'permissions.read'));
        $this->assertTrue($service->allows($context, 'runtime.read'));
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
            'application.read',
            'applications.read',
            'approval.decide',
            'approval.read',
            'business.modules.read',
            'dashboards.read',
            'dashboards.render',
            'data.records.create',
            'data.records.link',
            'data.records.read',
            'data.records.update',
            'documents.attach',
            'documents.read',
            'documents.update',
            'documents.upload',
            'entities.comment',
            'entities.read',
            'entities.tag',
            'files.read',
            'files.upload',
            'forms.draft',
            'forms.read',
            'forms.submit',
            'integrations.publish',
            'integrations.read',
            'jobs.read',
            'navigation.personalize',
            'navigation.read',
            'notifications.read',
            'organization.read',
            'personalization.read',
            'personalization.write',
            'reference.read',
            'reports.export',
            'reports.read',
            'reports.run',
            'rules.evaluate',
            'rules.read',
            'search.read',
            'tables.export',
            'tables.query',
            'tables.read',
            'task.read',
            'themes.read',
            'ui.personalize',
            'ui.read',
            'ui.render',
            'workflow.automation.read',
            'workflow.designer.export',
            'workflow.designer.read',
            'workflow.marketplace.export',
            'workflow.marketplace.install',
            'workflow.marketplace.read',
            'workflow.read',
            'workflow.runtime.read',
            'workspace.applications.read',
            'workspace.read',
        ], $permissions);
        $this->assertFalse($service->allows($context, 'organization.archive'));
    }
}
