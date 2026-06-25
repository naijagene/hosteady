<?php

namespace Tests\Feature\Modules\Lifecycle;

use App\Modules\Core\CoreModule;
use App\Modules\Sdk\Data\ModuleLifecycleContext;
use Tests\TestCase;

class AbstractApplicationModuleLifecycleTest extends TestCase
{
    public function test_default_lifecycle_hooks_are_no_ops(): void
    {
        $module = new CoreModule;
        $context = new ModuleLifecycleContext(
            moduleKey: 'core',
            organizationPublicId: 'org-1',
            workspacePublicId: 'ws-1',
        );

        $module->onInstall($context);
        $module->onUninstall($context);
        $module->onWorkspaceEnable($context);
        $module->onWorkspaceDisable($context);
        $module->onSettingsUpdated($context);
        $module->beforeRuntimeResolved($context);
        $module->afterRuntimeResolved($context);

        $this->assertTrue(true);
    }
}
