<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Partial = 'partial';
}
