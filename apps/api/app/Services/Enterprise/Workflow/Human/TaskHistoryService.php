<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Models\WorkflowHumanTask;
use App\Modules\Sdk\Workflow\Human\Data\TaskHistory;
use App\Modules\Sdk\Workflow\Human\Enums\HumanTaskStatus;

class TaskHistoryService
{
    /**
     * @return list<TaskHistory>
     */
    public function build(WorkflowHumanTask $task): array
    {
        $history = [];

        $history[] = new TaskHistory('created', $task->created_at?->toIso8601String() ?? now()->toIso8601String(), summary: 'Task created');

        $firstAssignment = true;
        foreach ($task->assignments()->orderBy('assigned_at')->get() as $assignment) {
            $history[] = new TaskHistory(
                eventType: $firstAssignment ? 'assigned' : 'reassigned',
                occurredAt: $assignment->assigned_at?->toIso8601String() ?? now()->toIso8601String(),
                summary: sprintf('Task %s via %s', $firstAssignment ? 'assigned' : 'reassigned', $assignment->assignment_type),
                metadata: ['assignment_public_id' => $assignment->public_id],
            );
            $firstAssignment = false;
        }

        if ($task->opened_at !== null) {
            $history[] = new TaskHistory('opened', $task->opened_at->toIso8601String(), summary: 'Task opened');
        }

        foreach ($task->comments()->orderBy('created_at')->get() as $comment) {
            $history[] = new TaskHistory(
                eventType: 'comment',
                occurredAt: $comment->created_at?->toIso8601String() ?? now()->toIso8601String(),
                summary: 'Comment added',
                metadata: ['comment_public_id' => $comment->public_id],
            );
        }

        foreach ($task->escalations()->orderBy('escalated_at')->get() as $escalation) {
            $history[] = new TaskHistory(
                eventType: 'escalate',
                occurredAt: $escalation->escalated_at?->toIso8601String() ?? now()->toIso8601String(),
                summary: $escalation->reason ?? 'Task escalated',
                metadata: ['escalation_public_id' => $escalation->public_id],
            );
        }

        $decision = $task->approvalDecision;
        if ($decision !== null && $decision->decided_at !== null) {
            $history[] = new TaskHistory(
                eventType: $decision->decision_type?->value === 'reject' ? 'reject' : 'approve',
                occurredAt: $decision->decided_at->toIso8601String(),
                summary: ucfirst((string) $decision->decision_type?->value),
                metadata: ['decision_public_id' => $decision->public_id],
            );
        }

        if ($task->completed_at !== null) {
            $history[] = new TaskHistory(
                eventType: in_array($task->status, [HumanTaskStatus::Rejected, HumanTaskStatus::Cancelled], true) ? 'close' : 'complete',
                occurredAt: $task->completed_at->toIso8601String(),
                summary: 'Task '.$task->status->value,
            );
        }

        return $history;
    }
}
