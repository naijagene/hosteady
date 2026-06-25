<?php

namespace Tests\Feature\Modules;

use App\Modules\Core\CoreModule;
use App\Modules\Demo\DemoModule;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Events\ModuleRegistryEvent;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Workspace\WorkspaceModule;
use Tests\TestCase;

class PlatformModulesTest extends TestCase
{
    public function test_core_module_manifest_matches_platform_catalog(): void
    {
        $module = new CoreModule;
        $manifest = $module->manifest();

        $this->assertSame(ModuleManifest::CURRENT_MANIFEST_VERSION, $manifest->manifestVersion);
        $this->assertSame(CoreModule::MODULE_UUID, $manifest->moduleUuid);
        $this->assertSame('core', $manifest->key);
        $this->assertTrue($manifest->isCore);
        $this->assertTrue($manifest->bootstrap);
    }

    public function test_workspace_module_manifest_matches_platform_catalog(): void
    {
        $module = new WorkspaceModule;
        $manifest = $module->manifest();

        $this->assertSame(WorkspaceModule::MODULE_UUID, $manifest->moduleUuid);
        $this->assertSame('workspace', $manifest->key);
        $this->assertTrue($manifest->isCore);
    }

    public function test_demo_module_manifest_matches_platform_catalog(): void
    {
        $module = new DemoModule;
        $manifest = $module->manifest();

        $this->assertSame(DemoModule::MODULE_UUID, $manifest->moduleUuid);
        $this->assertSame(['notifications', 'reporting'], $manifest->capabilities);
        $this->assertCount(2, $manifest->dependencies);
        $this->assertCount(3, $manifest->settings);
        $this->assertFalse($manifest->isCore);
    }

    public function test_platform_modules_pass_manifest_validation(): void
    {
        $validator = app(ModuleManifestValidator::class);

        $report = $validator->validateRegistry([
            new CoreModule,
            new WorkspaceModule,
            new DemoModule,
        ]);

        $this->assertTrue($report->isValid());
    }

    public function test_registry_event_constants_include_runtime_hooks(): void
    {
        $this->assertSame('beforeRuntimeResolved', ModuleRegistryEvent::BEFORE_RUNTIME_RESOLVED);
        $this->assertSame('afterRuntimeResolved', ModuleRegistryEvent::AFTER_RUNTIME_RESOLVED);
    }

    public function test_heos_doctor_command_is_reserved_in_config(): void
    {
        $doctor = config('heos.commands.doctor');

        $this->assertTrue($doctor['reserved']);
        $this->assertSame('heos:doctor', $doctor['name']);
    }
}
