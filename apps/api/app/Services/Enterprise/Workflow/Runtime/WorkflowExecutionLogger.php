<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowExecutionLog;
use App\Models\WorkflowInstance;

class WorkflowExecutionLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        WorkflowInstance $instance,
        string $level,
        string $message,
        ?string $stepId = null,
        array $context = [],
    ): void {
        WorkflowExecutionLog::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_execution_step_id' => $stepId,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'created_at' => now(),
        ]);
    }
}
