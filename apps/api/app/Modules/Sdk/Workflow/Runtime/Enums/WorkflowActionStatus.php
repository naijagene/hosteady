<?php

namespace App\Modules\Sdk\Workflow\Runtime\Enums;

enum WorkflowActionStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Waiting = 'waiting';
    case Skipped = 'skipped';
}
