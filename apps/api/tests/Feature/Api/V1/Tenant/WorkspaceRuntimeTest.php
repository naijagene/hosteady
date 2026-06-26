<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\ApplicationStatus;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Enums\WorkspaceStatus;
use App\Http\Middleware\ResolveTenantContext;
use App\Models\Application;
use App\Models\Role;
use App\Models\Workspace;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingMasker;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceRuntimeTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_returns_runtime_for_active_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime');

        $response->assertOk()
            ->assertJsonCount(3, 'data.active_applications')
            ->assertJsonStructure([
                'data' => [
                    'organization' => ['public_id', 'name', 'slug', 'status'],
                    'workspace' => ['public_id', 'name', 'slug', 'is_default', 'status'],
                    'membership' => ['public_id', 'status'],
                    'active_applications',
                    'active_application',
                    'runtime_version',
                    'settings_version',
                    'runtime_metadata' => ['generated_at', 'generated_by', 'schema_version'],
                    'capabilities' => ['audit', 'settings', 'workspace', 'notifications', 'storage', 'media', 'automation'],
                ],
            ])
            ->assertJsonPath('data.active_application', null)
            ->assertJsonPath('data.runtime_metadata.generated_by', 'WorkspaceRuntimeResolver')
            ->assertJsonPath('data.runtime_metadata.schema_version', 1)
            ->assertJsonPath('data.capabilities.audit', true)
            ->assertJsonPath('data.capabilities.notifications', true)
            ->assertJsonPath('data.capabilities.storage', true)
            ->assertJsonPath('data.capabilities.media', true);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_includes_settings_as_keyed_map_in_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-settings-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        app(WorkspaceSettingsService::class)->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime');

        $response->assertOk();

        $applications = collect($response->json('data.active_applications'));
        $demoRuntime = $applications->firstWhere('key', 'demo');

        $this->assertArrayHasKey('feature.enabled', $demoRuntime['settings']);
        $this->assertTrue($demoRuntime['settings']['feature.enabled']['value']);
        $this->assertEqualsCanonicalizing(['core', 'workspace'], $demoRuntime['dependencies']);
    }

    public function test_masks_sensitive_settings_in_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-mask-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        app(WorkspaceSettingsService::class)->bulkUpdate($context, $demo->public_id, [
            'secret.token' => ['value' => 'super-secret', 'type' => 'string', 'is_sensitive' => true],
        ]);

        $token = $this->issueToken($user);

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime');

        $applications = collect($response->json('data.active_applications'));
        $demoRuntime = $applications->firstWhere('key', 'demo');

        $this->assertSame(WorkspaceSettingMasker::MASK, $demoRuntime['settings']['secret.token']['value']);
        $this->assertTrue($demoRuntime['settings']['secret.token']['value_redacted']);
    }

    public function test_settings_and_runtime_versions_match_tenant_context(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-version-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        app(WorkspaceSettingsService::class)->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $token = $this->issueToken($user);

        $runtimeResponse = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk();

        $contextResponse = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertOk();

        $this->assertSame(
            $contextResponse->json('data.runtime_summary.settings_version'),
            $runtimeResponse->json('data.settings_version'),
        );
        $this->assertSame(
            $contextResponse->json('data.runtime_summary.runtime_version'),
            $runtimeResponse->json('data.runtime_version'),
        );
    }

    public function test_valid_application_header_resolves_active_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-header-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->withHeader(ResolveTenantContext::APPLICATION_HEADER, $demo->public_id)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk()
            ->assertJsonPath('data.active_application.key', 'demo')
            ->assertJsonPath('data.active_application.workspace_application_public_id', $demo->public_id);
    }

    public function test_malformed_application_header_returns_422(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-bad-header-org']);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->withHeader(ResolveTenantContext::APPLICATION_HEADER, 'not-a-uuid')
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertStatus(422)
            ->assertJsonPath('message', 'The X-HEOS-Application-Id header must be a valid UUID.');
    }

    public function test_unknown_application_header_returns_404(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-unknown-header-org']);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->withHeader(ResolveTenantContext::APPLICATION_HEADER, '01999999-9999-7999-8999-999999999999')
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertNotFound()
            ->assertJsonPath('message', 'Workspace application not found.');
    }

    public function test_rejects_cross_workspace_application_header(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-cross-workspace-org']);
        $organization = $this->findProvisionedOrganization($result);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

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
            ->withHeader(ResolveTenantContext::APPLICATION_HEADER, $demo->public_id)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertNotFound();
    }

    public function test_excludes_disabled_workspace_application_from_runtime(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);
        app(WorkspaceApplicationService::class)->disable($context, $demo->public_id);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk()
            ->assertJsonCount(2, 'data.active_applications');
    }

    public function test_excludes_org_disabled_application_from_runtime(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-org-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);
        app(ApplicationInstallationService::class)->disable($context, $demo->organizationApplication->public_id);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk()
            ->assertJsonCount(2, 'data.active_applications');
    }

    public function test_excludes_retired_catalog_application_from_runtime(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-retired-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);
        Application::query()->where('key', 'demo')->update(['status' => ApplicationStatus::Retired]);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk()
            ->assertJsonCount(2, 'data.active_applications');
    }

    public function test_member_can_read_runtime(): void
    {
        $this->seedHeosPlatform();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'runtime-api-member-org']);
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
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertOk()
            ->assertJsonCount(2, 'data.active_applications');
    }

    public function test_rejects_unauthenticated_requests(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-api-auth-org']);

        $this->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertUnauthorized();
    }

    public function test_requires_organization_header(): void
    {
        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->getJson('/api/v1/tenant/workspace/runtime')
            ->assertStatus(422)
            ->assertJsonPath('message', 'The X-HEOS-Organization-Id header is required.');
    }

    private function enableDemoApplication(TenantContext $context): \App\Models\WorkspaceApplication
    {
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        return app(WorkspaceApplicationService::class)->enable($context, $orgInstall->public_id);
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
