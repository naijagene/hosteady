<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowDefinitionData implements \JsonSerializable
{
    /**
     * @param  list<WorkflowNodeData>  $nodes
     * @param  list<WorkflowTransitionData>  $transitions
     * @param  list<WorkflowTriggerData>  $triggers
     * @param  list<WorkflowVariableData>  $variables
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $workflowKey,
        public string $name,
        public ?string $description = null,
        public ?string $moduleKey = null,
        public ?string $categoryPublicId = null,
        public array $nodes = [],
        public array $transitions = [],
        public array $triggers = [],
        public array $variables = [],
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $nodes = array_map(
            fn (array $node) => WorkflowNodeData::fromArray($node),
            is_array($payload['nodes'] ?? null) ? $payload['nodes'] : [],
        );
        $transitions = array_map(
            fn (array $transition) => WorkflowTransitionData::fromArray($transition),
            is_array($payload['transitions'] ?? null) ? $payload['transitions'] : [],
        );
        $triggers = array_map(
            fn (array $trigger) => WorkflowTriggerData::fromArray($trigger),
            is_array($payload['triggers'] ?? null) ? $payload['triggers'] : [],
        );
        $variables = array_map(
            fn (array $variable) => WorkflowVariableData::fromArray($variable),
            is_array($payload['variables'] ?? null) ? $payload['variables'] : [],
        );

        return new self(
            workflowKey: (string) ($payload['workflow_key'] ?? $payload['workflowKey'] ?? ''),
            name: (string) $payload['name'],
            description: isset($payload['description']) ? (string) $payload['description'] : null,
            moduleKey: isset($payload['module_key']) ? (string) $payload['module_key'] : null,
            categoryPublicId: isset($payload['category_public_id']) ? (string) $payload['category_public_id'] : null,
            nodes: $nodes,
            transitions: $transitions,
            triggers: $triggers,
            variables: $variables,
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workflow_key' => $this->workflowKey,
            'name' => $this->name,
            'description' => $this->description,
            'module_key' => $this->moduleKey,
            'category_public_id' => $this->categoryPublicId,
            'nodes' => array_map(fn (WorkflowNodeData $node) => $node->toArray(), $this->nodes),
            'transitions' => array_map(fn (WorkflowTransitionData $transition) => $transition->toArray(), $this->transitions),
            'triggers' => array_map(fn (WorkflowTriggerData $trigger) => $trigger->toArray(), $this->triggers),
            'variables' => array_map(fn (WorkflowVariableData $variable) => $variable->toArray(), $this->variables),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDefinitionJson(): array
    {
        return [
            'nodes' => array_map(fn (WorkflowNodeData $node) => $node->toArray(), $this->nodes),
            'transitions' => array_map(fn (WorkflowTransitionData $transition) => $transition->toArray(), $this->transitions),
            'triggers' => array_map(fn (WorkflowTriggerData $trigger) => $trigger->toArray(), $this->triggers),
            'variables' => array_map(fn (WorkflowVariableData $variable) => $variable->toArray(), $this->variables),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
