<?php

namespace App\Modules\Sdk\Workflow\Enums;

enum WorkflowNodeType: string
{
    case Start = 'start';
    case End = 'end';
    case Task = 'task';
    case Approval = 'approval';
    case Condition = 'condition';
    case Parallel = 'parallel';
    case Merge = 'merge';
    case Event = 'event';
    case Subprocess = 'subprocess';
    case Wait = 'wait';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
