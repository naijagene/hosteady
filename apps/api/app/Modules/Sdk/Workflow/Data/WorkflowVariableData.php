<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowVariableData implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $defaultValue
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public ?array $defaultValue = null,
        public bool $isRequired = false,
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            key: (string) ($payload['key'] ?? $payload['variable_key'] ?? ''),
            label: (string) $payload['label'],
            type: (string) $payload['type'],
            defaultValue: is_array($payload['default_value'] ?? null) ? $payload['default_value'] : null,
            isRequired: (bool) ($payload['is_required'] ?? false),
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'default_value' => $this->defaultValue,
            'is_required' => $this->isRequired,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
