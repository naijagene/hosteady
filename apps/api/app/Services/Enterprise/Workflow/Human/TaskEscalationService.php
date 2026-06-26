<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\WorkflowHumanTask;
use App\Models\WorkflowTaskEscalation;
use App\Modules\Sdk\Workflow\Human\Data\TaskEscalation;

class TaskEscalationService
{
    public function __construct(
        private readonly HumanTaskAuditRecorder $auditRecorder,
    ) {
    }

    public function escalate(
        WorkflowHumanTask $task,
        string $rule,
        ?string $reason = null,
        ?string $escalatedMembershipId = null,
        ?string $escalatedUserId = null,
    ): TaskEscalation {
        $escalation = WorkflowTaskEscalation::query()->create([
            'workflow_human_task_id' => $task->id,
            'escalation_rule' => $rule,
            'escalated_user_id' => $escalatedUserId,
            'escalated_membership_id' => $escalatedMembershipId,
            'escalated_at' => now(),
            'reason' => $reason,
            'metadata' => ['placeholder' => true],
        ]);

        $this->auditRecorder->recordEscalated($task, $escalation->public_id);

        return new TaskEscalation(
            publicId: $escalation->public_id,
            escalationRule: $escalation->escalation_rule,
            escalatedAt: $escalation->escalated_at?->toIso8601String(),
            reason: $escalation->reason,
            metadata: $escalation->metadata ?? [],
        );
    }
}
