<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

readonly class WorkflowExecutionResult implements \JsonSerializable
{
    /**
     * @param  list<WorkflowExecutionReference>  $steps
     * @param  list<WorkflowVariableSnapshot>  $variables
     */
    public function __construct(
        public WorkflowInstanceReference $instance,
        public array $steps = [],
        public array $variables = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'instance' => $this->instance->toArray(),
            'steps' => array_map(fn (WorkflowExecutionReference $step) => $step->toArray(), $this->steps),
            'variables' => array_map(fn (WorkflowVariableSnapshot $variable) => $variable->toArray(), $this->variables),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
