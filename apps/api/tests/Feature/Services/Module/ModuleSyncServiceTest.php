<?php

namespace Tests\Feature\Services\Module;

use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Models\Permission;
use App\Modules\Core\CoreModule;
use App\Modules\Demo\DemoModule;
use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRegistryReader;
use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModulePermission;
use App\Modules\Sdk\Data\ModuleRouteCollection;
use App\Modules\Sdk\Data\ModuleSyncOptions;
use App\Modules\Sdk\Data\ModuleValidationIssue;
use App\Modules\Sdk\Data\ModuleValidationReport;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Sdk\ModuleRegistry;
use App\Modules\Sdk\SimpleModuleRegistryEventDispatcher;
use App\Modules\Workspace\WorkspaceModule;
use App\Services\Module\ModuleSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private ModuleSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ModuleSyncService::class);
    }

    public function test_creates_application_rows_from_modules(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $result = $this->service->sync($registry, new ModuleSyncOptions);

        $this->assertTrue($result->success);
        $this->assertSame(3, Application::query()->count());
        $this->assertNotNull(Application::query()->where('key', 'demo')->first());
    }

    public function test_updates_existing_application_rows(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);
        $this->service->sync($registry, new ModuleSyncOptions);

        Application::query()->where('key', 'demo')->update(['name' => 'Stale Demo Name']);

        $result = $this->service->sync($registry, new ModuleSyncOptions);

        $this->assertTrue($result->success);
        $this->assertSame('Demo Application', Application::query()->where('key', 'demo')->value('name'));
        $this->assertGreaterThan(0, $result->updated);
    }

    public function test_syncs_capabilities_and_dependencies(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $this->service->sync($registry, new ModuleSyncOptions);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        $this->assertEqualsCanonicalizing(['notifications', 'reporting'], $demo->capabilities);
        $this->assertEqualsCanonicalizing(['core', 'workspace'], $demo->dependencies);
    }

    public function test_syncs_module_uuid_and_manifest_version(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $this->service->sync($registry, new ModuleSyncOptions);

        $core = Application::query()->where('key', 'core')->firstOrFail();

        $this->assertSame(CoreModule::MODULE_UUID, $core->module_uuid);
        $this->assertSame(1, $core->manifest_version);
    }

    public function test_syncs_setting_definitions(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $this->service->sync($registry, new ModuleSyncOptions);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $keys = ApplicationSettingDefinition::query()
            ->where('application_id', $demo->id)
            ->orderBy('sort_order')
            ->pluck('setting_key')
            ->all();

        $this->assertSame(['feature.enabled', 'notification.email', 'secret.token'], $keys);
    }

    public function test_syncs_module_permissions(): void
    {
        $registry = $this->registryWith(
            new CoreModule,
            new WorkspaceModule,
            new PermissionsDemoModule,
        );

        $result = $this->service->sync($registry, new ModuleSyncOptions);

        $this->assertTrue($result->success);
        $this->assertNotNull(Permission::query()->where('key', 'demo.records.read')->first());
        $this->assertSame('demo', Permission::query()->where('key', 'demo.records.read')->value('domain'));
    }

    public function test_second_sync_run_is_idempotent(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $first = $this->service->sync($registry, new ModuleSyncOptions);
        $second = $this->service->sync($registry, new ModuleSyncOptions);

        $this->assertTrue($first->success);
        $this->assertTrue($second->success);
        $this->assertSame(3, Application::query()->count());
        $this->assertGreaterThan(0, $second->unchanged);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $result = $this->service->sync($registry, new ModuleSyncOptions(dryRun: true));

        $this->assertTrue($result->success);
        $this->assertSame(0, Application::query()->count());
        $this->assertGreaterThan(0, $result->created);
    }

    public function test_module_filter_syncs_only_requested_module(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $result = $this->service->sync($registry, new ModuleSyncOptions(moduleKey: 'demo'));

        $this->assertTrue($result->success);
        $this->assertSame(1, $result->modulesScanned);
        $this->assertSame(1, Application::query()->count());
        $this->assertSame('demo', Application::query()->value('key'));
    }

    public function test_validation_failure_prevents_writes(): void
    {
        $registry = new InvalidValidationRegistry;

        $result = $this->service->sync($registry, new ModuleSyncOptions);

        $this->assertFalse($result->success);
        $this->assertSame(0, Application::query()->count());
    }

    public function test_unknown_module_filter_returns_error(): void
    {
        $registry = $this->registryWith(new CoreModule, new WorkspaceModule, new DemoModule);

        $result = $this->service->sync($registry, new ModuleSyncOptions(moduleKey: 'missing'));

        $this->assertFalse($result->success);
        $this->assertSame('unknown_module', $result->errors[0]->code);
    }

    public function test_includes_role_assignment_deferred_note(): void
    {
        $registry = $this->registryWith(new CoreModule);

        $result = $this->service->sync($registry, new ModuleSyncOptions);

        $this->assertContains(
            'Module permission role assignment is deferred to a future slice.',
            $result->notes,
        );
    }

    private function registryWith(ApplicationModule ...$modules): ModuleRegistry
    {
        $registry = new ModuleRegistry(new ModuleManifestValidator, new SimpleModuleRegistryEventDispatcher, $this->service);

        foreach ($modules as $module) {
            $registry->register($module);
        }

        return $registry;
    }
}

class PermissionsDemoModule extends DemoModule
{
    public function permissions(): array
    {
        return [
            new ModulePermission('demo.records.read', 'Read Demo Records', 'Read demo module records.'),
        ];
    }
}

class InvalidValidationRegistry implements ModuleRegistryReader
{
    public function all(): array
    {
        return [];
    }

    public function findByKey(string $key): ?\App\Modules\Sdk\Contracts\ApplicationModule
    {
        return null;
    }

    public function validate(): ModuleValidationReport
    {
        return new ModuleValidationReport([
            new ModuleValidationIssue('invalid_manifest', 'Manifest validation failed.', 'demo'),
        ]);
    }
}
