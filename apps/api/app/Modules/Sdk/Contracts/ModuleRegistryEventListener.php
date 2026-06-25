<?php

namespace App\Modules\Sdk\Contracts;

interface ModuleRegistryEventListener
{
    /**
     * @param  list<mixed>  $payload
     */
    public function handle(string $event, array $payload = []): void;
}
