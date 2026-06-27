<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;

interface EntityWorkflowProvider
{
    public function moduleKey(): string;

    public function entityKey(): string;

    public function triggerLifecycle(EntityLifecycleEvent $event): void;
}
