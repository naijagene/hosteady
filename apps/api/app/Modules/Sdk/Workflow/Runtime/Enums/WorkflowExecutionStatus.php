<?php

namespace App\Modules\Sdk\Workflow\Runtime\Enums;

enum WorkflowExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Waiting = 'waiting';
}
