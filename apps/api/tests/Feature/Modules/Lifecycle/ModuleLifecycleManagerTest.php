<?php

namespace Tests\Feature\Modules\Lifecycle;

use App\Modules\Core\CoreModule;
use App\Modules\Sdk\AbstractApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRegistryEventListener;
use App\Modules\Sdk\Data\ModuleLifecycleContext;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Events\ModuleRegistryEvent;
use App\Modules\Sdk\Exceptions\LifecycleException;
use App\Modules\Sdk\Lifecycle\LifecycleOperation;
use App\Modules\Sdk\Lifecycle\ModuleLifecycleDispatcher;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Sdk\ModuleRegistry;
use App\Modules\Sdk\SimpleModuleRegistryEventDispatcher;
use App\Services\Audit\ModuleLifecycleAuditRecorder;
use App\Services\Module\ModuleLifecycleManager;
use App\Services\Module\ModuleSyncService;
use App\Services\Runtime\RuntimeCacheInvalidator;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class ModuleLifecycleManagerTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_install_runs_module_hook_and_returns_success(): void
    {
        $manager = $this->managerWithModule(new TrackingLifecycleModule('trackable'));

        $result = $manager->install($this->tenantContext(), 'trackable');

        $this->assertTrue($result->success);
        $this->assertSame(LifecycleOperation::Install, $result->operation);
        $this->assertSame(['onInstall'], TrackingLifecycleModule::$calls);
    }

    public function test_uninstall_runs_module_hook(): void
    {
        $manager = $this->managerWithModule(new TrackingLifecycleModule('trackable'));

        $result = $manager->uninstall($this->tenantContext(), 'trackable');

        $this->assertTrue($result->success);
        $this->assertSame(['onUninstall'], TrackingLifecycleModule::$calls);
    }

    public function test_enable_workspace_runs_module_hook(): void
    {
        $manager = $this->managerWithModule(new TrackingLifecycleModule('trackable'));

        $result = $manager->enableWorkspace($this->tenantContext(), 'trackable');

        $this->assertTrue($result->success);
        $this->assertSame(['onWorkspaceEnable'], TrackingLifecycleModule::$calls);
    }

    public function test_disable_workspace_runs_module_hook(): void
    {
        $manager = $this->managerWithModule(new TrackingLifecycleModule('trackable'));

        $result = $manager->disableWorkspace($this->tenantContext(), 'trackable');

        $this->assertTrue($result->success);
        $this->assertSame(['onWorkspaceDisable'], TrackingLifecycleModule::$calls);
    }

    public function test_settings_updated_runs_module_hook(): void
    {
        $manager = $this->managerWithModule(new TrackingLifecycleModule('trackable'));

        $result = $manager->settingsUpdated($this->tenantContext(), 'trackable', ['setting_keys' => ['feature.enabled']]);

        $this->assertTrue($result->success);
        $this->assertSame(['onSettingsUpdated'], TrackingLifecycleModule::$calls);
    }

    public function test_runtime_resolved_runs_before_and_after_hooks(): void
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-runtime-org']);
        $context = $this->buildTenantContext($user, $result);

        $manager = $this->managerWithModule(new TrackingLifecycleModule('core'));
        TrackingLifecycleModule::$calls = [];

        $payload = $manager->runtimeResolved($context, fn () => (object) ['runtimeVersion' => 'rv-1']);

        $this->assertSame('rv-1', $payload['runtime']->runtimeVersion);
        $this->assertSame(['beforeRuntimeResolved', 'afterRuntimeResolved'], TrackingLifecycleModule::$calls);
    }

    public function test_hook_failure_throws_lifecycle_exception_for_transactional_operations(): void
    {
        $manager = $this->managerWithModule(new FailingLifecycleModule('failing'));

        $this->expectException(LifecycleException::class);

        $manager->runInstallHooks($this->tenantContext(), 'failing');
    }

    public function test_audit_failure_does_not_stop_lifecycle(): void
    {
        $audit = Mockery::mock(ModuleLifecycleAuditRecorder::class);
        $audit->shouldReceive('recordInstallCompleted')->andThrow(new \RuntimeException('audit failed'));

        $manager = $this->managerWithModule(new TrackingLifecycleModule('trackable'), $audit);

        $result = $manager->completeInstall($this->tenantContext(), 'trackable');

        $this->assertTrue($result->success);
    }

    public function test_runtime_hook_failure_does_not_block_runtime_generation(): void
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-failure-org']);
        $context = $this->buildTenantContext($user, $result);

        $manager = $this->managerWithModule(new FailingLifecycleModule('core'));
        $payload = $manager->runtimeResolved($context, fn () => (object) ['runtimeVersion' => 'rv-2']);

        $this->assertSame('rv-2', $payload['runtime']->runtimeVersion);
        $this->assertNotEmpty($payload['results'][0]->warnings);
    }

    public function test_multiple_modules_execute_in_deterministic_order(): void
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'multi-module-org']);
        $context = $this->buildTenantContext($user, $result);

        $registry = new ModuleRegistry(new ModuleManifestValidator, new SimpleModuleRegistryEventDispatcher, app(ModuleSyncService::class));
        $registry->register(new OrderedLifecycleModule('core'));
        $registry->register(new OrderedLifecycleModule('workspace'));
        OrderedLifecycleModule::$calls = [];

        $manager = $this->buildManager($registry);

        $manager->runtimeResolved($context, fn () => (object) ['runtimeVersion' => 'rv-3']);

        $this->assertSame(['core:before', 'workspace:before', 'core:after', 'workspace:after'], OrderedLifecycleModule::$calls);
    }

    public function test_skips_unregistered_module_without_failure(): void
    {
        $manager = $this->managerWithModule(new CoreModule);

        $result = $manager->install($this->tenantContext(), 'missing-module');

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->warnings);
    }

    private function managerWithModule(
        \App\Modules\Sdk\Contracts\ApplicationModule $module,
        ?ModuleLifecycleAuditRecorder $audit = null,
    ): ModuleLifecycleManager {
        $registry = new ModuleRegistry(new ModuleManifestValidator, new SimpleModuleRegistryEventDispatcher, app(ModuleSyncService::class));
        $registry->register($module);

        return $this->buildManager($registry, $audit);
    }

    private function buildManager(
        ModuleRegistry $registry,
        ?ModuleLifecycleAuditRecorder $audit = null,
    ): ModuleLifecycleManager {
        return new ModuleLifecycleManager(
            registry: $registry,
            dispatcher: new ModuleLifecycleDispatcher(new SimpleModuleRegistryEventDispatcher),
            auditRecorder: $audit ?? app(ModuleLifecycleAuditRecorder::class),
            runtimeCacheInvalidator: app(RuntimeCacheInvalidator::class),
        );
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();
        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'lifecycle-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }
}

class TrackingLifecycleModule extends AbstractApplicationModule
{
    /** @var list<string> */
    public static array $calls = [];

    public function __construct(private readonly string $moduleKey)
    {
        self::$calls = [];
    }

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function name(): string
    {
        return ucfirst($this->moduleKey);
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function manifest(): ModuleManifest
    {
        return $this->buildManifest(
            moduleUuid: '01900000-0000-7000-8000-000000000099',
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: false,
        );
    }

    public function onInstall(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'onInstall';
    }

    public function onUninstall(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'onUninstall';
    }

    public function onWorkspaceEnable(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'onWorkspaceEnable';
    }

    public function onWorkspaceDisable(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'onWorkspaceDisable';
    }

    public function onSettingsUpdated(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'onSettingsUpdated';
    }

    public function beforeRuntimeResolved(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'beforeRuntimeResolved';
    }

    public function afterRuntimeResolved(ModuleLifecycleContext $context): void
    {
        self::$calls[] = 'afterRuntimeResolved';
    }
}

class FailingLifecycleModule extends TrackingLifecycleModule
{
    public function onInstall(ModuleLifecycleContext $context): void
    {
        throw new \RuntimeException('install failed');
    }

    public function beforeRuntimeResolved(ModuleLifecycleContext $context): void
    {
        throw new \RuntimeException('runtime before failed');
    }
}

class OrderedLifecycleModule extends AbstractApplicationModule
{
    /** @var list<string> */
    public static array $calls = [];

    public function __construct(private readonly string $moduleKey)
    {
    }

    public function key(): string
    {
        return $this->moduleKey;
    }

    public function name(): string
    {
        return ucfirst($this->moduleKey);
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function manifest(): ModuleManifest
    {
        $suffix = $this->moduleKey === 'core' ? '11' : '12';

        return $this->buildManifest(
            moduleUuid: '01900000-0000-7000-8000-0000000000'.$suffix,
            key: $this->key(),
            name: $this->name(),
            version: $this->version(),
            isCore: true,
        );
    }

    public function beforeRuntimeResolved(ModuleLifecycleContext $context): void
    {
        self::$calls[] = $this->moduleKey.':before';
    }

    public function afterRuntimeResolved(ModuleLifecycleContext $context): void
    {
        self::$calls[] = $this->moduleKey.':after';
    }
}
