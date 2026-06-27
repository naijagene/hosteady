<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;

interface EntityLifecycleHandler
{
    public function handle(EntityLifecycleEvent $event): void;
}
