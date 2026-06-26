<?php

namespace Tests\Feature\Services\WorkspaceApplication;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizationApplicationStatus;
use App\Enums\WorkspaceApplicationStatus;
use App\Models\Application;
use App\Models\WorkspaceApplication;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\Runtime\Data\RuntimeManifest;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Services\WorkspaceApplication\WorkspaceRuntimeVersionCalculator;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceRuntimeResolverTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private WorkspaceRuntimeProvider $runtimeProvider;

    private WorkspaceApplicationService $workspaceApplicationService;

    private WorkspaceSettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runtimeProvider = app(WorkspaceRuntimeProvider::class);
        $this->workspaceApplicationService = app(WorkspaceApplicationService::class);
        $this->settingsService = app(WorkspaceSettingsService::class);
    }

    public function test_returns_active_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-active-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertCount(3, $runtime->activeApplications);
        $keys = array_map(fn ($app) => $app->key, $runtime->activeApplications);
        $this->assertContains('core', $keys);
        $this->assertContains('workspace', $keys);
        $this->assertContains('demo', $keys);
        $this->assertSame($demo->public_id, collect($runtime->activeApplications)->firstWhere('key', 'demo')->workspaceApplicationPublicId);
    }

    public function test_excludes_disabled_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->workspaceApplicationService->disable($context, $demo->public_id);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertCount(2, $runtime->activeApplications);
        $this->assertNull(collect($runtime->activeApplications)->firstWhere('key', 'demo'));
    }

    public function test_excludes_archived_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-archived-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->workspaceApplicationService->disable($context, $demo->public_id);
        $this->workspaceApplicationService->archive($context, $demo->public_id);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertCount(2, $runtime->activeApplications);
        $this->assertNull(collect($runtime->activeApplications)->firstWhere('key', 'demo'));
    }

    public function test_excludes_removed_workspace_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-removed-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->workspaceApplicationService->disable($context, $demo->public_id);
        $this->workspaceApplicationService->archive($context, $demo->public_id);
        $this->workspaceApplicationService->remove($context, $demo->public_id);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertCount(2, $runtime->activeApplications);
    }

    public function test_excludes_org_disabled_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-org-disabled-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);
        $orgInstall = $demo->organizationApplication;

        app(ApplicationInstallationService::class)->disable($context, $orgInstall->public_id);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertCount(2, $runtime->activeApplications);
        $this->assertNull(collect($runtime->activeApplications)->firstWhere('key', 'demo'));
    }

    public function test_excludes_retired_catalog_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-retired-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        Application::query()->where('key', 'demo')->update(['status' => ApplicationStatus::Retired]);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertCount(2, $runtime->activeApplications);
        $this->assertNull(collect($runtime->activeApplications)->firstWhere('key', 'demo'));
    }

    public function test_includes_settings_as_keyed_map(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-settings-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $runtime = $this->runtimeProvider->resolve($context);
        $demoRuntime = collect($runtime->activeApplications)->firstWhere('key', 'demo');

        $this->assertArrayHasKey('feature.enabled', $demoRuntime->settings);
        $this->assertTrue($demoRuntime->settings['feature.enabled']->value);
        $this->assertSame('boolean', $demoRuntime->settings['feature.enabled']->type);
    }

    public function test_masks_sensitive_settings(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-sensitive-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'secret.token' => ['value' => 'super-secret', 'type' => 'string', 'is_sensitive' => true],
        ]);

        $runtime = $this->runtimeProvider->resolve($context);
        $demoRuntime = collect($runtime->activeApplications)->firstWhere('key', 'demo');

        $this->assertSame('***', $demoRuntime->settings['secret.token']->value);
        $this->assertTrue($demoRuntime->settings['secret.token']->valueRedacted);
    }

    public function test_settings_version_matches_settings_service(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-settings-version-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertSame($this->settingsService->resolveSettingsVersion($context), $runtime->settingsVersion);
    }

    public function test_runtime_version_is_stable_when_unchanged(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-stable-org']);
        $context = $this->buildTenantContext($user, $result);

        $first = $this->runtimeProvider->resolveSummary($context);
        $second = $this->runtimeProvider->resolveSummary($context);

        $this->assertSame($first->runtimeVersion, $second->runtimeVersion);
    }

    public function test_runtime_version_changes_when_active_app_set_changes(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-app-change-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $before = $this->runtimeProvider->resolveSummary($context)->runtimeVersion;

        $this->workspaceApplicationService->disable($context, $demo->public_id);

        $after = $this->runtimeProvider->resolveSummary($context)->runtimeVersion;

        $this->assertNotSame($before, $after);
    }

    public function test_runtime_version_changes_when_setting_version_changes(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-setting-change-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => false, 'type' => 'boolean'],
        ]);

        $before = $this->runtimeProvider->resolveSummary($context)->runtimeVersion;

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $after = $this->runtimeProvider->resolveSummary($context)->runtimeVersion;

        $this->assertNotSame($before, $after);
    }

    public function test_resolves_active_application_from_public_id(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-active-app-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = $this->enableDemoApplication($context);

        $runtime = $this->runtimeProvider->resolve($context, $demo->public_id);

        $this->assertNotNull($runtime->activeApplication);
        $this->assertSame($demo->public_id, $runtime->activeApplication->workspaceApplicationPublicId);
        $this->assertSame('demo', $runtime->activeApplication->key);
    }

    public function test_includes_runtime_metadata_and_capabilities(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-metadata-org']);
        $context = $this->buildTenantContext($user, $result);

        $runtime = $this->runtimeProvider->resolve($context);

        $this->assertSame('WorkspaceRuntimeResolver', $runtime->runtimeMetadata['generated_by']);
        $this->assertSame(1, $runtime->runtimeMetadata['schema_version']);
        $this->assertNotEmpty($runtime->runtimeMetadata['generated_at']);
        $this->assertTrue($runtime->capabilities['audit']);
        $this->assertTrue($runtime->capabilities['settings']);
        $this->assertTrue($runtime->capabilities['workspace']);
        $this->assertFalse($runtime->capabilities['automation']);
        $this->assertTrue($runtime->capabilities['notifications']);
    }

    public function test_includes_application_dependencies_from_catalog(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-deps-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);

        $runtime = $this->runtimeProvider->resolve($context);

        $core = collect($runtime->activeApplications)->firstWhere('key', 'core');
        $demo = collect($runtime->activeApplications)->firstWhere('key', 'demo');

        $this->assertSame([], $core->dependencies);
        $this->assertEqualsCanonicalizing(['core', 'workspace'], $demo->dependencies);
    }

    public function test_version_calculator_is_deterministic(): void
    {
        $calculator = app(WorkspaceRuntimeVersionCalculator::class);

        $manifest = new RuntimeManifest(
            fingerprintApplications: [
                [
                    'key' => 'demo',
                    'workspace_application_status' => 'active',
                    'organization_application_status' => 'active',
                    'catalog_application_status' => 'active',
                    'enabled_version' => '1.0.0',
                    'catalog_version' => '1.0.0',
                    'capabilities' => [],
                    'dependencies' => [],
                    'definitions' => [],
                    'settings' => [
                        [
                            'setting_key' => 'feature.enabled',
                            'value_hash' => 'abc123',
                            'value_source' => 'workspace',
                            'version' => 1,
                            'setting_type' => 'boolean',
                        ],
                    ],
                ],
            ],
            applications: [],
            applicationsByPublicId: [],
        );

        $this->assertSame($calculator->calculate($manifest), $calculator->calculate($manifest));
    }

    private function enableDemoApplication(TenantContext $context): WorkspaceApplication
    {
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        return $this->workspaceApplicationService->enable($context, $orgInstall->public_id);
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
