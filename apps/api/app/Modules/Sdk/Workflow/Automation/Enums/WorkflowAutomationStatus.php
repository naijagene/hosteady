<?php

namespace App\Modules\Sdk\Workflow\Automation\Enums;

enum WorkflowAutomationStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
