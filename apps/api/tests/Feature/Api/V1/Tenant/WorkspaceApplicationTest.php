<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Application;
use App\Models\Role;
use App\Models\WorkspaceApplication;
use App\Services\Application\ApplicationInstallationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceApplicationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_lists_workspace_applications_for_owner(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-api-list-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/applications');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'public_id',
                        'status',
                        'enabled_version',
                        'is_bootstrap',
                        'organization_application_public_id',
                        'application' => ['public_id', 'key', 'name'],
                    ],
                ],
            ]);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_enables_application_in_workspace(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-api-enable-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/workspace/applications', [
                'organization_application_public_id' => $orgInstall->public_id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.enabled_version', '1.0.0')
            ->assertJsonPath('data.is_bootstrap', false)
            ->assertJsonPath('data.application.key', 'demo');

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_lists_available_applications_with_already_enabled_false(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-api-available-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/applications/available');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.already_enabled', false)
            ->assertJsonPath('data.0.organization_application_public_id', $orgInstall->public_id);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_disables_archives_and_removes_demo_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-api-lifecycle-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $workspaceApplication = app(\App\Services\WorkspaceApplication\WorkspaceApplicationService::class)
            ->enable($context, $orgInstall->public_id);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson('/api/v1/tenant/workspace/applications/'.$workspaceApplication->public_id.'/disable')
            ->assertOk()
            ->assertJsonPath('data.status', 'disabled');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson('/api/v1/tenant/workspace/applications/'.$workspaceApplication->public_id.'/archive')
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson('/api/v1/tenant/workspace/applications/'.$workspaceApplication->public_id.'/enable')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->deleteJson('/api/v1/tenant/workspace/applications/'.$workspaceApplication->public_id)
            ->assertNoContent();
    }

    public function test_blocks_manage_actions_for_core_workspace_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-api-core-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $coreWorkspaceApplication = WorkspaceApplication::query()
            ->where('workspace_id', $workspace->id)
            ->whereHas('application', fn ($query) => $query->where('key', 'core'))
            ->firstOrFail();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson('/api/v1/tenant/workspace/applications/'.$coreWorkspaceApplication->public_id.'/disable')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Core workspace applications cannot be disabled, archived, or removed.');
    }

    public function test_member_can_read_but_not_enable_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'wa-api-member-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $context = $this->buildTenantContext($owner, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

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
            ->getJson('/api/v1/tenant/workspace/applications')
            ->assertOk();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/workspace/applications', [
                'organization_application_public_id' => $orgInstall->public_id,
            ])
            ->assertForbidden();
    }

    public function test_tenant_context_includes_runtime_summary(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'wa-api-context-org']);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context');

        $response->assertOk()
            ->assertJsonPath('data.runtime_summary.active_application_count', 2)
            ->assertJsonPath('data.runtime_summary.settings_version', 0);

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $response->json('data.runtime_summary.runtime_version'),
        );
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
