<?php

namespace App\Modules\Sdk\Workflow\Designer\Data;

readonly class WorkflowCanvasEdge implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $source,
        public string $target,
        public ?string $label = null,
        public ?string $condition = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            source: (string) $payload['source'],
            target: (string) $payload['target'],
            label: isset($payload['label']) ? (string) $payload['label'] : null,
            condition: isset($payload['condition']) ? (string) $payload['condition'] : null,
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'target' => $this->target,
            'label' => $this->label,
            'condition' => $this->condition,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
