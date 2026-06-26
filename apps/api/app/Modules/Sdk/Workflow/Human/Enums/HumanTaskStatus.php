<?php

namespace App\Modules\Sdk\Workflow\Human\Enums;

enum HumanTaskStatus: string
{
    case Created = 'created';
    case Assigned = 'assigned';
    case Opened = 'opened';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
