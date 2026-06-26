<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowCanvas implements \JsonSerializable
{
    /**
     * @param  list<WorkflowCanvasNode>  $nodes
     * @param  list<WorkflowCanvasEdge>  $edges
     */
    public function __construct(
        public array $nodes = [],
        public array $edges = [],
        public ?WorkflowCanvasViewport $viewport = null,
        public ?WorkflowDesignerMetadata $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $nodes = array_map(
            fn (array $node) => WorkflowCanvasNode::fromArray($node),
            is_array($payload['nodes'] ?? null) ? $payload['nodes'] : [],
        );
        $edges = array_map(
            fn (array $edge) => WorkflowCanvasEdge::fromArray($edge),
            is_array($payload['edges'] ?? null) ? $payload['edges'] : [],
        );

        return new self(
            nodes: $nodes,
            edges: $edges,
            viewport: is_array($payload['viewport'] ?? null)
                ? WorkflowCanvasViewport::fromArray($payload['viewport'])
                : null,
            metadata: is_array($payload['metadata'] ?? null)
                ? WorkflowDesignerMetadata::fromArray($payload['metadata'])
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_map(fn (WorkflowCanvasNode $node) => $node->toArray(), $this->nodes),
            'edges' => array_map(fn (WorkflowCanvasEdge $edge) => $edge->toArray(), $this->edges),
            'viewport' => $this->viewport?->toArray(),
            'metadata' => $this->metadata?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
