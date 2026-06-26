<?php

namespace App\Modules\Sdk\Workflow\Human\Enums;

enum ApprovalDecisionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
}
