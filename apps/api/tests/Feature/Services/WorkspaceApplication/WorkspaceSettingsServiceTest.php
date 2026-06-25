<?php

namespace Tests\Feature\Services\WorkspaceApplication;

use App\Enums\WorkspaceSettingChangeType;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingTypeException;
use App\Exceptions\WorkspaceApplication\SensitiveSettingDowngradeException;
use App\Exceptions\WorkspaceApplication\UnknownWorkspaceSettingKeysException;
use App\Models\Application;
use App\Models\WorkspaceApplication;
use App\Models\WorkspaceApplicationSetting;
use App\Models\WorkspaceApplicationSettingHistory;
use App\Services\Application\ApplicationInstallationService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingMasker;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceSettingsServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private WorkspaceSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WorkspaceSettingsService::class);
    }

    public function test_creates_setting_with_version_one_and_history(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-create-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $settings = $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => [
                'value' => 'ops@example.com',
                'type' => 'string',
                'is_sensitive' => false,
            ],
        ]);

        $this->assertCount(1, $settings);
        $this->assertSame(1, $settings->first()->version);
        $this->assertSame('ops@example.com', $settings->first()->setting_value);

        $this->assertDatabaseHas('workspace_application_setting_history', [
            'workspace_application_id' => $workspaceApplication->id,
            'setting_key' => 'notification.email',
            'version' => 1,
            'change_type' => WorkspaceSettingChangeType::Created->value,
        ]);
    }

    public function test_updates_setting_and_increments_version(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-update-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'feature.enabled' => ['value' => false, 'type' => 'boolean'],
        ]);

        $settings = $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $this->assertSame(2, $settings->first()->version);
        $this->assertDatabaseHas('workspace_application_setting_history', [
            'setting_key' => 'feature.enabled',
            'change_type' => WorkspaceSettingChangeType::Updated->value,
            'version' => 2,
        ]);
    }

    public function test_does_not_increment_version_when_normalized_value_unchanged(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-no-bump-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => '  ops@example.com  ', 'type' => 'string'],
        ]);

        $settings = $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
        ]);

        $this->assertSame(1, $settings->first()->version);
        $this->assertSame(1, WorkspaceApplicationSettingHistory::query()->count());
    }

    public function test_bulk_update_is_transactional(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-txn-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        try {
            $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
                'valid.key' => ['value' => 'ok', 'type' => 'string'],
                'invalid.key' => ['value' => 'not-a-number', 'type' => 'integer'],
            ]);
        } catch (InvalidWorkspaceSettingTypeException) {
            // expected
        }

        $this->assertSame(0, WorkspaceApplicationSetting::query()->count());
        $this->assertSame(0, WorkspaceApplicationSettingHistory::query()->count());
    }

    public function test_resets_selected_settings(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-reset-selected-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $settings = $this->service->reset($context, $workspaceApplication->public_id, ['notification.email']);

        $this->assertCount(1, $settings);
        $this->assertSame('feature.enabled', $settings->first()->setting_key);
        $this->assertSoftDeleted('workspace_application_settings', [
            'workspace_application_id' => $workspaceApplication->id,
            'setting_key' => 'notification.email',
        ]);
        $this->assertDatabaseHas('workspace_application_setting_history', [
            'setting_key' => 'notification.email',
            'change_type' => WorkspaceSettingChangeType::Reset->value,
            'after_value' => null,
        ]);
    }

    public function test_resets_all_settings_when_keys_omitted(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-reset-all-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $settings = $this->service->reset($context, $workspaceApplication->public_id, null);

        $this->assertCount(0, $settings);
        $this->assertSame(0, WorkspaceApplicationSetting::query()->whereNull('deleted_at')->count());
        $this->assertSame(2, WorkspaceApplicationSettingHistory::query()
            ->where('change_type', WorkspaceSettingChangeType::Reset->value)
            ->count());
    }

    public function test_rejects_unknown_reset_keys(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-unknown-reset-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->expectException(UnknownWorkspaceSettingKeysException::class);

        $this->service->reset($context, $workspaceApplication->public_id, ['missing.key']);
    }

    public function test_filters_history_by_setting_key(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-history-filter-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $paginator = $this->service->history($context, $workspaceApplication->public_id, 'notification.email');

        $this->assertSame(1, $paginator->total());
        $this->assertSame('notification.email', $paginator->items()[0]->setting_key);
    }

    public function test_validates_string_boolean_integer_float_array_and_json_values(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-types-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $settings = $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'string.key' => ['value' => 'hello', 'type' => 'string'],
            'boolean.key' => ['value' => 'true', 'type' => 'boolean'],
            'integer.key' => ['value' => '42', 'type' => 'integer'],
            'float.key' => ['value' => '3.14', 'type' => 'float'],
            'array.key' => ['value' => ['a', 'b'], 'type' => 'array'],
            'json.key' => ['value' => ['nested' => ['value' => 1]], 'type' => 'json'],
        ]);

        $this->assertCount(6, $settings);
        $this->assertSame('hello', $settings->firstWhere('setting_key', 'string.key')->setting_value);
        $this->assertTrue($settings->firstWhere('setting_key', 'boolean.key')->setting_value);
        $this->assertSame(42, $settings->firstWhere('setting_key', 'integer.key')->setting_value);
        $this->assertSame(3.14, $settings->firstWhere('setting_key', 'float.key')->setting_value);
        $this->assertSame(['a', 'b'], $settings->firstWhere('setting_key', 'array.key')->setting_value);
        $this->assertSame(['nested' => ['value' => 1]], $settings->firstWhere('setting_key', 'json.key')->setting_value);
    }

    public function test_rejects_invalid_type_values(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-invalid-type-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->expectException(InvalidWorkspaceSettingTypeException::class);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'integer.key' => ['value' => 'not-a-number', 'type' => 'integer'],
        ]);
    }

    public function test_masks_sensitive_history_values(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-sensitive-history-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'secret.token' => ['value' => 'super-secret', 'type' => 'string', 'is_sensitive' => true],
        ]);

        $history = WorkspaceApplicationSettingHistory::query()->firstOrFail();

        $this->assertSame(WorkspaceSettingMasker::MASK, $history->after_value);
    }

    public function test_blocks_sensitive_downgrade(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-sensitive-downgrade-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'secret.token' => ['value' => 'super-secret', 'type' => 'string', 'is_sensitive' => true],
        ]);

        $this->expectException(SensitiveSettingDowngradeException::class);

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'secret.token' => ['value' => 'super-secret', 'type' => 'string', 'is_sensitive' => false],
        ]);
    }

    public function test_resolve_settings_version_returns_max_version_for_active_applications(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'ws-version-org']);
        $context = $this->buildTenantContext($user, $result);
        $workspaceApplication = $this->enableDemoApplication($context);

        $this->assertSame(0, $this->service->resolveSettingsVersion($context));

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => 'ops@example.com', 'type' => 'string'],
        ]);

        $this->assertSame(1, $this->service->resolveSettingsVersion($context));

        $this->service->bulkUpdate($context, $workspaceApplication->public_id, [
            'notification.email' => ['value' => 'changed@example.com', 'type' => 'string'],
        ]);

        $this->assertSame(2, $this->service->resolveSettingsVersion($context));
    }

    private function enableDemoApplication(TenantContext $context): WorkspaceApplication
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
