<?php

namespace App\Modules\Sdk;

use App\Modules\Sdk\Contracts\ModuleRegistryEventDispatcher;
use App\Modules\Sdk\Contracts\ModuleRegistryEventListener;

class SimpleModuleRegistryEventDispatcher implements ModuleRegistryEventDispatcher
{
    /**
     * @var list<ModuleRegistryEventListener>
     */
    private array $listeners = [];

    public function addListener(ModuleRegistryEventListener $listener): void
    {
        $this->listeners[] = $listener;
    }

    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners as $listener) {
            $listener->handle($event, $payload);
        }
    }
}
