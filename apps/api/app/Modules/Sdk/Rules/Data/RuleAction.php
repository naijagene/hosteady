<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleAction implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public ?string $field,
        public mixed $value,
        public string $severity = 'warning',
        public ?string $message,
        public array $metadata = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? $data['type'] ?? ''),
            field: isset($data['field']) ? (string) $data['field'] : (isset($data['field']) ? (string) $data['field'] : null),
            value: $data['value'] ?? $data['value'] ?? null,
            severity: (string) ($data['severity'] ?? $data['severity'] ?? ''),
            message: isset($data['message']) ? (string) $data['message'] : (isset($data['message']) ? (string) $data['message'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'field' => $this->field,
            'value' => $this->value,
            'severity' => $this->severity,
            'message' => $this->message,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
