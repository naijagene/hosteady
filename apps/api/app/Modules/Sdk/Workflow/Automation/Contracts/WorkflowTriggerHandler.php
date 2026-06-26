<?php

namespace App\Modules\Sdk\Workflow\Automation\Contracts;

use App\Modules\Sdk\Enterprise\Data\PlatformEventData;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationResult;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule;

interface WorkflowTriggerHandler
{
    public function executeRule(
        WorkflowAutomationRule $rule,
        string $triggerSource,
        ?PlatformEventData $event = null,
        ?array $payload = null,
    ): WorkflowAutomationResult;
}
