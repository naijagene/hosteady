<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Application;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class OrganizationApplicationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_installs_demo_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-install-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $demo->public_id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.installed_version', '1.0.0')
            ->assertJsonPath('data.installed_by_membership_public_id', $result->membershipPublicId)
            ->assertJsonPath('data.application.public_id', $demo->public_id)
            ->assertJsonPath('data.application.key', 'demo');

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_lists_installed_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-list-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $installResponse = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $demo->public_id,
            ]);

        $installationPublicId = $installResponse->json('data.public_id');

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.public_id', $installationPublicId);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_disables_and_enables_demo_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-toggle-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $installationPublicId = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $demo->public_id,
            ])
            ->json('data.public_id');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson("/api/v1/tenant/applications/{$installationPublicId}/disable")
            ->assertOk()
            ->assertJsonPath('data.status', 'disabled');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson("/api/v1/tenant/applications/{$installationPublicId}/enable")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_uninstalls_demo_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-uninstall-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $installationPublicId = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $demo->public_id,
            ])
            ->json('data.public_id');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->deleteJson("/api/v1/tenant/applications/{$installationPublicId}")
            ->assertNoContent();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/applications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_blocks_disable_for_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-core-disable-org']);
        $core = Application::query()->where('key', 'core')->firstOrFail();
        $token = $this->issueToken($user);

        $installationPublicId = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $core->public_id,
            ])
            ->json('data.public_id');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->patchJson("/api/v1/tenant/applications/{$installationPublicId}/disable")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Core applications cannot be disabled or uninstalled.');
    }

    public function test_blocks_uninstall_for_core_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-core-uninstall-org']);
        $core = Application::query()->where('key', 'core')->firstOrFail();
        $token = $this->issueToken($user);

        $installationPublicId = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $core->public_id,
            ])
            ->json('data.public_id');

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->deleteJson("/api/v1/tenant/applications/{$installationPublicId}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Core applications cannot be disabled or uninstalled.');
    }

    public function test_denies_install_without_permission(): void
    {
        $this->seedHeosPlatform();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'api-permission-org']);
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

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($member);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', [
                'application_public_id' => $demo->public_id,
            ])
            ->assertForbidden();
    }

    public function test_rejects_duplicate_install(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'api-duplicate-org']);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $token = $this->issueToken($user);

        $payload = ['application_public_id' => $demo->public_id];

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', $payload)
            ->assertCreated();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/applications', $payload)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Application is already installed for this organization.');
    }
}
