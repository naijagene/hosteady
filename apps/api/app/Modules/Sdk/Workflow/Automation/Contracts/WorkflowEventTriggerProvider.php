<?php

namespace App\Modules\Sdk\Workflow\Automation\Contracts;

use App\Modules\Sdk\Enterprise\Data\PlatformEventData;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowEventSubscription;

interface WorkflowEventTriggerProvider
{
    /**
     * @return list<WorkflowEventSubscription>
     */
    public function subscriptionsForEvent(PlatformEventData $event): array;
}
