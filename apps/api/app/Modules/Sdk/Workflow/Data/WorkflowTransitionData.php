<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowTransitionData implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $from,
        public string $to,
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
            from: (string) $payload['from'],
            to: (string) $payload['to'],
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
            'from' => $this->from,
            'to' => $this->to,
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
