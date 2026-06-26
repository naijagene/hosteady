<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\PlatformEventData;

interface ModuleEventSubscriber
{
    /**
     * @return list<string>
     */
    public function subscribedEvents(): array;

    public function handle(PlatformEventData $event): void;
}
