<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowExecutionEvent;
use App\Models\WorkflowExecutionStep;
use App\Models\WorkflowInstance;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionReference;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowExecutionStatus;

class WorkflowExecutionTracker
{
    public function startStep(
        WorkflowInstance $instance,
        string $nodeId,
        string $nodeType,
    ): WorkflowExecutionStep {
        return WorkflowExecutionStep::query()->create([
            'workflow_instance_id' => $instance->id,
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'status' => WorkflowExecutionStatus::Running,
            'started_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    public function completeStep(
        WorkflowExecutionStep $step,
        WorkflowExecutionStatus $status,
        ?array $result = null,
        array $warnings = [],
        array $errors = [],
    ): WorkflowExecutionReference {
        $completedAt = now();
        $durationMs = $step->started_at !== null
            ? (int) $step->started_at->diffInMilliseconds($completedAt)
            : null;

        $step->update([
            'status' => $status,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
            'result' => $result,
            'warnings' => $warnings,
            'errors' => $errors,
        ]);

        return new WorkflowExecutionReference(
            publicId: $step->public_id,
            nodeId: $step->node_id,
            nodeType: $step->node_type,
            status: $step->status->value,
            startedAt: $step->started_at?->toIso8601String(),
            completedAt: $step->completed_at?->toIso8601String(),
            durationMs: $durationMs,
            result: $result,
            warnings: $warnings,
            errors: $errors,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordEvent(WorkflowInstance $instance, string $eventType, array $payload = []): void
    {
        WorkflowExecutionEvent::query()->create([
            'workflow_instance_id' => $instance->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
