<?php

namespace App\Modules\Sdk\Contracts;

interface ModuleRegistryEventDispatcher
{
    /**
     * @param  list<mixed>  $payload
     */
    public function dispatch(string $event, array $payload = []): void;
}
