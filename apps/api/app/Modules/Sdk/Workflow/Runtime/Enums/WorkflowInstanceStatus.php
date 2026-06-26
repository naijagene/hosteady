<?php

namespace App\Modules\Sdk\Workflow\Runtime\Enums;

enum WorkflowInstanceStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
