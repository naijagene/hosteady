<?php

namespace Tests\Feature\Modules\Sdk;

use App\Modules\Demo\DemoModule;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRegistryEventListener;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModuleRouteCollection;
use App\Modules\Sdk\Events\ModuleRegistryEvent;
use App\Modules\Sdk\Exceptions\DuplicateModuleKeyException;
use App\Modules\Sdk\Exceptions\InvalidModuleManifestException;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Sdk\ModuleRegistry;
use App\Modules\Sdk\SimpleModuleRegistryEventDispatcher;
use App\Modules\Workspace\WorkspaceModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    use RefreshDatabase;
    private ModuleRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $events = new SimpleModuleRegistryEventDispatcher;
        $this->registry = new ModuleRegistry(new ModuleManifestValidator, $events);
    }

    public function test_registers_module_and_finds_by_key(): void
    {
        $module = new WorkspaceModule;

        $this->registry->register($module);

        $this->assertSame('workspace', $this->registry->findByKey('workspace')?->key());
        $this->assertCount(1, $this->registry->all());
    }

    public function test_rejects_duplicate_module_key(): void
    {
        $this->registry->register(new WorkspaceModule);

        $this->expectException(DuplicateModuleKeyException::class);

        $this->registry->register(new WorkspaceModule);
    }

    public function test_rejects_invalid_manifest_at_registration(): void
    {
        $this->expectException(InvalidModuleManifestException::class);

        $this->registry->register(new InvalidKeyModule);
    }

    public function test_validate_reports_registry_issues(): void
    {
        $this->registry->register(new \App\Modules\Core\CoreModule);
        $this->registry->register(new WorkspaceModule);
        $this->registry->register(new DemoModule);

        $report = $this->registry->validate();

        $this->assertTrue($report->isValid());
    }

    public function test_sync_to_database_returns_module_sync_result(): void
    {
        $events = new SimpleModuleRegistryEventDispatcher;
        $sync = app(\App\Services\Module\ModuleSyncService::class);
        $registry = new ModuleRegistry(new ModuleManifestValidator, $events, $sync);
        $registry->register(new \App\Modules\Core\CoreModule);
        $registry->register(new WorkspaceModule);

        $result = $registry->syncToDatabase(new \App\Modules\Sdk\Data\ModuleSyncOptions);

        $this->assertTrue($result->success);
        $this->assertSame(2, $result->modulesScanned);
    }

    public function test_sync_to_database_dispatches_before_and_after_sync_events(): void
    {
        $events = [];

        $dispatcher = new SimpleModuleRegistryEventDispatcher;
        $dispatcher->addListener(new class($events) implements ModuleRegistryEventListener {
            public function __construct(private array &$events)
            {
            }

            public function handle(string $event, array $payload = []): void
            {
                $this->events[] = $event;
            }
        });

        $sync = app(\App\Services\Module\ModuleSyncService::class);
        $registry = new ModuleRegistry(new ModuleManifestValidator, $dispatcher, $sync);
        $registry->register(new WorkspaceModule);

        $registry->syncToDatabase(new \App\Modules\Sdk\Data\ModuleSyncOptions);

        $this->assertContains(ModuleRegistryEvent::BEFORE_SYNC, $events);
        $this->assertContains(ModuleRegistryEvent::AFTER_SYNC, $events);
    }

    public function test_dispatches_before_and_after_register_events(): void
    {
        $events = [];

        $dispatcher = new SimpleModuleRegistryEventDispatcher;
        $dispatcher->addListener(new class($events) implements ModuleRegistryEventListener {
            public function __construct(private array &$events)
            {
            }

            public function handle(string $event, array $payload = []): void
            {
                $this->events[] = $event;
            }
        });

        $registry = new ModuleRegistry(new ModuleManifestValidator, $dispatcher);
        $registry->register(new WorkspaceModule);

        $this->assertSame([
            ModuleRegistryEvent::BEFORE_REGISTER,
            ModuleRegistryEvent::AFTER_REGISTER,
        ], $events);
    }

    public function test_application_registry_contains_platform_modules(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertNotNull($registry->findByKey('core'));
        $this->assertNotNull($registry->findByKey('workspace'));
        $this->assertNotNull($registry->findByKey('demo'));
        $this->assertCount(3, $registry->all());
    }
}

class InvalidKeyModule implements ApplicationModule
{
    public function key(): string
    {
        return 'INVALID';
    }

    public function name(): string
    {
        return 'Invalid';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            manifestVersion: 1,
            moduleUuid: 'not-a-uuid',
            key: 'INVALID',
            name: 'Invalid',
            version: '1.0.0',
            category: null,
            icon: null,
            description: null,
            isCore: false,
            bootstrap: false,
            capabilities: [],
            dependencies: [],
            permissions: [],
            settings: [],
            navigation: [],
            routes: new ModuleRouteCollection,
        );
    }

    public function permissions(): array
    {
        return [];
    }

    public function settingDefinitions(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return [];
    }

    public function dependencies(): array
    {
        return [];
    }

    public function navigation(): array
    {
        return [];
    }

    public function routes(): ModuleRouteCollection
    {
        return new ModuleRouteCollection;
    }

    public function boot(): void
    {
    }

    public function health(\App\Modules\Sdk\Contracts\ModuleHealthContext $context): \App\Modules\Sdk\Data\ModuleHealthReport
    {
        return \App\Modules\Sdk\Data\ModuleHealthReport::healthy();
    }

    public function onInstall(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function onUninstall(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function onWorkspaceEnable(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function onWorkspaceDisable(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function onSettingsUpdated(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function beforeRuntimeResolved(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function afterRuntimeResolved(\App\Modules\Sdk\Data\ModuleLifecycleContext $context): void
    {
    }

    public function contributeRuntime(\App\Modules\Sdk\Contracts\ModuleRuntimeContext $context): \App\Modules\Sdk\Runtime\RuntimeContribution
    {
        return \App\Modules\Sdk\Runtime\RuntimeContribution::empty($this->key());
    }
}
