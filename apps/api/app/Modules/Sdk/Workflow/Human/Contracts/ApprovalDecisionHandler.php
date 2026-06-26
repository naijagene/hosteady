<?php

namespace App\Modules\Sdk\Workflow\Human\Contracts;

use App\Modules\Sdk\Workflow\Human\Data\ApprovalDecision;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;

interface ApprovalDecisionHandler
{
    public function afterApproved(HumanTaskReference $task, ApprovalDecision $decision): void;

    public function afterRejected(HumanTaskReference $task, ApprovalDecision $decision): void;
}
