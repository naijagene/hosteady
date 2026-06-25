<?php

namespace Tests\Feature\Modules\Lifecycle;

use App\Modules\Sdk\Contracts\ModuleRegistryEventListener;
use App\Modules\Sdk\Data\ModuleLifecycleContext;
use App\Modules\Sdk\Events\ModuleRegistryEvent;
use App\Modules\Sdk\Lifecycle\LifecycleOperation;
use App\Modules\Sdk\Lifecycle\ModuleLifecycleDispatcher;
use App\Modules\Sdk\SimpleModuleRegistryEventDispatcher;
use Tests\Feature\Modules\Lifecycle\ModuleLifecycleManagerTest;
use Tests\TestCase;

class ModuleLifecycleDispatcherTest extends TestCase
{
    public function test_dispatches_hooks_in_expected_order(): void
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

        $module = new TrackingLifecycleModule('trackable');
        $context = new ModuleLifecycleContext(
            moduleKey: 'trackable',
            organizationPublicId: 'org-1',
            workspacePublicId: 'ws-1',
        );

        (new ModuleLifecycleDispatcher($dispatcher))->execute(
            $module,
            LifecycleOperation::Install,
            $context,
        );

        $this->assertSame([
            ModuleRegistryEvent::BEFORE_LIFECYCLE,
            ModuleRegistryEvent::BEFORE_INSTALL,
            ModuleRegistryEvent::AFTER_INSTALL,
            ModuleRegistryEvent::AFTER_LIFECYCLE,
        ], $events);
        $this->assertSame(['onInstall'], TrackingLifecycleModule::$calls);
    }
}
