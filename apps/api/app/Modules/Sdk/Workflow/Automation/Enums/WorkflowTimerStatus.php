<?php

namespace App\Modules\Sdk\Workflow\Automation\Enums;

enum WorkflowTimerStatus: string
{
    case Active = 'active';
    case Executed = 'executed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
