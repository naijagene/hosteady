<?php

namespace App\Modules\Sdk\Workflow\Automation\Enums;

enum WorkflowTriggerExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
