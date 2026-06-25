<?php

namespace Tests\Feature\Api\V1\Tenant;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Enums\WorkspaceStatus;
use App\Models\Application;
use App\Models\Role;
use App\Models\Workspace;
use App\Models\WorkspaceApplicationSettingHistory;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingMasker;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceApplicationSettingsTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_lists_settings_for_workspace_application(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-list-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                ],
            ])
            ->assertOk();

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/settings?workspace_application_public_id='.$workspaceApplication->public_id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting_key', 'notification.email')
            ->assertJsonPath('data.0.value', 'ops@example.com')
            ->assertJsonPath('data.0.value_redacted', false);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_masks_sensitive_setting_values_in_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-mask-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'secret.token' => [
                        'value' => 'super-secret',
                        'type' => 'string',
                        'is_sensitive' => true,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.value', WorkspaceSettingMasker::MASK)
            ->assertJsonPath('data.0.value_redacted', true);
    }

    public function test_masks_sensitive_values_in_history_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-history-mask-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'secret.token' => [
                        'value' => 'super-secret',
                        'type' => 'string',
                        'is_sensitive' => true,
                    ],
                ],
            ])
            ->assertOk();

        $response = $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/settings/history?workspace_application_public_id='.$workspaceApplication->public_id);

        $response->assertOk()
            ->assertJsonPath('data.0.after_value', WorkspaceSettingMasker::MASK);

        $this->assertResponseUsesPublicIdsOnly($response->json());
    }

    public function test_returns_settings_history_with_filtering(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-history-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                    'feature.enabled' => ['value' => true, 'type' => 'boolean'],
                ],
            ])
            ->assertOk();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/settings/history?workspace_application_public_id='.$workspaceApplication->public_id.'&setting_key=notification.email')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.change_type', 'created');
    }

    public function test_resets_selected_settings_via_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-reset-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                    'feature.enabled' => ['value' => true, 'type' => 'boolean'],
                ],
            ])
            ->assertOk();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/workspace/settings/reset', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'keys' => ['notification.email'],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting_key', 'feature.enabled');

        $this->assertSame(1, WorkspaceApplicationSettingHistory::query()
            ->where('change_type', 'reset')
            ->count());
    }

    public function test_resets_all_settings_when_keys_empty(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-reset-all-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                ],
            ])
            ->assertOk();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/workspace/settings/reset', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_rejects_unknown_reset_keys(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-unknown-reset-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->postJson('/api/v1/tenant/workspace/settings/reset', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'keys' => ['missing.key'],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Unknown setting keys: missing.key.');
    }

    public function test_rejects_invalid_setting_type_in_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-invalid-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'integer.key' => ['value' => 'not-a-number', 'type' => 'integer'],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_blocks_sensitive_downgrade_via_api(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-downgrade-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'secret.token' => [
                        'value' => 'super-secret',
                        'type' => 'string',
                        'is_sensitive' => true,
                    ],
                ],
            ])
            ->assertOk();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'secret.token' => [
                        'value' => 'super-secret',
                        'type' => 'string',
                        'is_sensitive' => false,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Setting [secret.token] cannot be changed from sensitive to non-sensitive.');
    }

    public function test_member_policy_denies_configure(): void
    {
        $this->seedHeosPlatform();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'ws-policy-unit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $context = $this->buildTenantContext($owner, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $member = $this->createActiveUser();
        $memberRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $memberMembership = $organization->memberships()->create([
            'user_id' => $member->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'join_method' => JoinMethod::Invitation,
        ]);
        $memberMembership->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $memberContext = TenantContext::fromModels($member, $organization, $memberMembership, $workspace);
        app()->instance(TenantContext::class, $memberContext);

        $policy = app(\App\Policies\WorkspaceApplicationPolicy::class);

        $this->assertFalse($policy->configure($member, $workspaceApplication));
        $this->assertTrue($policy->view($member, $workspaceApplication));
    }

    public function test_member_can_read_but_cannot_configure_settings(): void
    {
        $this->seedHeosPlatform();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'ws-api-member-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $context = $this->buildTenantContext($owner, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

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

        app(\App\Services\WorkspaceApplication\WorkspaceSettingsService::class)->bulkUpdate(
            $context,
            $workspaceApplication->public_id,
            ['notification.email' => ['value' => 'ops@example.com', 'type' => 'string']],
        );

        $memberToken = $this->issueToken($member);

        $this->withBearerToken($memberToken)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/settings?workspace_application_public_id='.$workspaceApplication->public_id)
            ->assertOk();

        $this->withBearerToken($memberToken)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'changed@example.com', 'type' => 'string'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_blocks_settings_access_for_wrong_workspace(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-wrong-workspace-org']);
        $organization = $this->findProvisionedOrganization($result);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

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
            ->getJson('/api/v1/tenant/workspace/settings?workspace_application_public_id='.$workspaceApplication->public_id)
            ->assertNotFound();
    }

    public function test_blocks_update_for_disabled_and_archived_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $service = app(WorkspaceApplicationService::class);
        $token = $this->issueToken($user);

        $service->disable($context, $workspaceApplication->public_id);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Workspace application must be active to modify settings.');

        $service->archive($context, $workspaceApplication->public_id);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_allows_read_for_disabled_and_archived_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-read-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $service = app(WorkspaceApplicationService::class);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                ],
            ])
            ->assertOk();

        $service->disable($context, $workspaceApplication->public_id);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/settings?workspace_application_public_id='.$workspaceApplication->public_id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $service->archive($context, $workspaceApplication->public_id);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/workspace/settings?workspace_application_public_id='.$workspaceApplication->public_id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_tenant_context_settings_version_updates_after_setting_changes(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-api-context-version-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertOk()
            ->assertJsonPath('data.runtime_summary.settings_version', 0);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->putJson('/api/v1/tenant/workspace/settings', [
                'workspace_application_public_id' => $workspaceApplication->public_id,
                'settings' => [
                    'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
                ],
            ])
            ->assertOk();

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/context')
            ->assertOk()
            ->assertJsonPath('data.runtime_summary.settings_version', 1);
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
