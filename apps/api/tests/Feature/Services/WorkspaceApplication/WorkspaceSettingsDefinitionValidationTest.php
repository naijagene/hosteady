<?php

namespace Tests\Feature\Services\WorkspaceApplication;

use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingTypeException;
use App\Exceptions\WorkspaceApplication\UnknownWorkspaceSettingKeysException;
use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceSettingsDefinitionValidationTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private WorkspaceSettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsService = app(WorkspaceSettingsService::class);
    }

    public function test_rejects_unknown_setting_keys_when_definitions_exist(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        $this->expectException(UnknownWorkspaceSettingKeysException::class);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'unknown.key' => ['value' => true, 'type' => 'boolean'],
        ]);
    }

    public function test_rejects_invalid_value_for_definition_rules(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        $this->expectException(InvalidWorkspaceSettingTypeException::class);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'notification.email' => ['value' => 'not-an-email', 'type' => 'string'],
        ]);
    }

    public function test_uses_definition_sensitive_flag_for_secret_token(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        $settings = $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'secret.token' => ['value' => 'super-secret', 'type' => 'string'],
        ]);

        $this->assertTrue($settings->firstWhere('setting_key', 'secret.token')->is_sensitive);
    }

    public function test_accepts_known_definition_keys_without_extra_metadata(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        $settings = $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
            'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
        ]);

        $this->assertSame(2, $settings->count());
    }

    public function test_core_application_remains_permissive_without_definitions(): void
    {
        $context = $this->tenantContextWithDemo();
        $core = Application::query()->where('key', 'core')->firstOrFail();
        $coreWorkspaceApplication = \App\Models\WorkspaceApplication::query()
            ->where('workspace_id', $context->workspace->id)
            ->where('application_id', $core->id)
            ->firstOrFail();

        $settings = $this->settingsService->bulkUpdate($context, $coreWorkspaceApplication->public_id, [
            'custom.flag' => ['value' => true, 'type' => 'boolean'],
        ]);

        $this->assertSame('custom.flag', $settings->first()->setting_key);
    }

    public function test_settings_version_only_reflects_persisted_rows(): void
    {
        $context = $this->tenantContextWithDemo();

        $before = $this->settingsService->resolveSettingsVersion($context);

        app(\App\Services\WorkspaceApplication\WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertSame($before, $this->settingsService->resolveSettingsVersion($context));
    }

    public function test_runtime_version_changes_when_default_precedence_changes(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);
        $runtimeProvider = app(\App\Services\WorkspaceApplication\WorkspaceRuntimeProvider::class);

        $before = $runtimeProvider->resolveSummary($context)->runtimeVersion;

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $after = $runtimeProvider->resolveSummary($context)->runtimeVersion;

        $this->assertNotSame($before, $after);
    }

    public function test_invalidation_occurs_after_settings_bulk_update(): void
    {
        config(['heos.runtime_cache.enabled' => true]);

        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);
        $store = app(\App\Services\Runtime\RuntimeCacheStore::class);
        $beforeGeneration = $store->currentGeneration($context->organizationPublicId, $context->workspacePublicId);

        $this->settingsService->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $afterGeneration = $store->currentGeneration($context->organizationPublicId, $context->workspacePublicId);

        $this->assertSame($beforeGeneration + 1, $afterGeneration);
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'settings-definition-validation-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);

        return $context;
    }

    private function demoWorkspaceApplication(TenantContext $context): \App\Models\WorkspaceApplication
    {
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        return \App\Models\WorkspaceApplication::query()
            ->where('workspace_id', $context->workspace->id)
            ->where('application_id', $demo->id)
            ->firstOrFail();
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
