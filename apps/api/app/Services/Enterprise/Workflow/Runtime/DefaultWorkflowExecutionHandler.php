<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Modules\Sdk\Workflow\Enums\WorkflowNodeType;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowExecutionHandler;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowActionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowActionStatus;
use App\Services\Enterprise\Workflow\Human\HumanTaskRuntimeBridge;

class DefaultWorkflowExecutionHandler implements WorkflowExecutionHandler
{
    /**
     * @var list<string>
     */
    private const SUPPORTED = [
        'start', 'end', 'task', 'approval', 'condition', 'parallel', 'merge', 'event', 'subprocess', 'wait',
    ];

    public function __construct(
        private readonly HumanTaskRuntimeBridge $humanTaskBridge,
    ) {
    }

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
        if ($this->humanTaskBridge->supports($nodeType)) {
            return $this->humanTaskBridge->handle($nodeType, $node, $context, $variables);
        }

        return match ($nodeType) {
            WorkflowNodeType::Subprocess->value => new WorkflowActionResult(
                status: WorkflowActionStatus::Succeeded->value,
                warnings: ['Subprocess execution is not implemented yet.'],
                metadata: ['placeholder' => true],
            ),
            default => new WorkflowActionResult(status: WorkflowActionStatus::Succeeded->value),
        };
    }
}
