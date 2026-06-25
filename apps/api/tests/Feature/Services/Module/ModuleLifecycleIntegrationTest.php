<?php

namespace Tests\Feature\Services\Module;

use App\Enums\AuditAction;
use App\Enums\OrganizationApplicationStatus;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\OrganizationApplication;
use App\Modules\Sdk\Data\LifecycleResult;
use App\Modules\Sdk\Exceptions\LifecycleException;
use App\Modules\Sdk\Lifecycle\LifecycleOperation;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Module\ModuleLifecycleManager;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ModuleLifecycleIntegrationTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_application_installation_runs_module_install_lifecycle(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-install-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ModuleInstallCompleted->value,
        ]);
    }

    public function test_install_lifecycle_failure_rolls_back_database_changes(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-rollback-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $lifecycle = Mockery::mock(ModuleLifecycleManager::class);
        $lifecycle->shouldReceive('runInstallHooks')->andThrow(new LifecycleException(
            LifecycleResult::failed('demo', LifecycleOperation::Install, new \RuntimeException('boom')),
        ));
        $lifecycle->shouldReceive('completeInstall')->never();
        $this->app->instance(ModuleLifecycleManager::class, $lifecycle);

        try {
            app(ApplicationInstallationService::class)->install($context, $demo->public_id);
            $this->fail('Expected LifecycleException was not thrown.');
        } catch (LifecycleException) {
        }

        $this->assertSame(0, OrganizationApplication::query()
            ->where('organization_id', $this->findProvisionedOrganization($result)->id)
            ->where('application_id', $demo->id)
            ->where('status', '!=', OrganizationApplicationStatus::Uninstalled)
            ->count());
    }

    public function test_workspace_enable_runs_module_workspace_enable_lifecycle(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-enable-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        app(WorkspaceApplicationService::class)->enable(
            $context,
            $installation->public_id,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ModuleWorkspaceEnabled->value,
        ]);
    }

    public function test_workspace_settings_update_runs_module_settings_lifecycle(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-settings-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $installation = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        $workspaceApplication = app(WorkspaceApplicationService::class)->enable($context, $installation->public_id);

        app(WorkspaceSettingsService::class)->bulkUpdate(
            $context,
            $workspaceApplication->public_id,
            [
                'feature.enabled' => [
                    'value' => true,
                    'type' => 'boolean',
                ],
            ],
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::ModuleSettingsUpdated->value,
        ]);
    }

    public function test_runtime_resolver_runs_module_runtime_lifecycle(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-resolver-org']);
        $context = $this->buildTenantContext($user, $result);

        app(WorkspaceRuntimeResolver::class)->resolve($context);

        $this->assertTrue(AuditLog::query()
            ->where('action', AuditAction::ModuleRuntimeBefore->value)
            ->exists());
        $this->assertTrue(AuditLog::query()
            ->where('action', AuditAction::ModuleRuntimeAfter->value)
            ->exists());
    }
}
