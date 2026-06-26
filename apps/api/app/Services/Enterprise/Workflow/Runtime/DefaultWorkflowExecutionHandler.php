<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Modules\Sdk\Workflow\Enums\WorkflowNodeType;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowExecutionHandler;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowActionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionReference;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowActionStatus;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowExecutionStatus;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Modules\Sdk\Workflow\Runtime\Exceptions\WorkflowExecutionException;
use App\Models\WorkflowInstance;

class DefaultWorkflowExecutionHandler implements WorkflowExecutionHandler
{
    /**
     * @var list<string>
     */
    private const SUPPORTED = [
        'start', 'end', 'task', 'approval', 'condition', 'parallel', 'merge', 'event', 'subprocess', 'wait',
    ];

    public function supports(string $nodeType): bool
    {
        return in_array($nodeType, self::SUPPORTED, true);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     */
    public function execute(
        string $nodeType,
        array $node,
        WorkflowExecutionContext $context,
        array $variables,
    ): WorkflowActionResult {
        return match ($nodeType) {
            WorkflowNodeType::Wait->value => new WorkflowActionResult(
                status: WorkflowActionStatus::Waiting->value,
                metadata: ['placeholder' => true],
                halt: true,
            ),
            WorkflowNodeType::Subprocess->value => new WorkflowActionResult(
                status: WorkflowActionStatus::Succeeded->value,
                warnings: ['Subprocess execution is not implemented yet.'],
                metadata: ['placeholder' => true],
            ),
            WorkflowNodeType::Approval->value => new WorkflowActionResult(
                status: WorkflowActionStatus::Succeeded->value,
                metadata: ['approval' => 'placeholder'],
            ),
            default => new WorkflowActionResult(status: WorkflowActionStatus::Succeeded->value),
        };
    }
}
