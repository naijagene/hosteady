<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowPreviewPayload implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $canvas
     * @param  list<WorkflowNodeTemplate>  $templates
     * @param  list<array<string, mixed>>  $issues
     */
    public function __construct(
        public string $workflowDefinitionPublicId,
        public array $definition,
        public array $canvas,
        public array $templates = [],
        public array $issues = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workflow_definition_public_id' => $this->workflowDefinitionPublicId,
            'definition' => $this->definition,
            'canvas' => $this->canvas,
            'templates' => array_map(fn (WorkflowNodeTemplate $t) => $t->toArray(), $this->templates),
            'issues' => $this->issues,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
