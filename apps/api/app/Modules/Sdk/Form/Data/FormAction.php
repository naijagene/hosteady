<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormAction implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public ?string $handler = null,
        public ?string $confirmMessage = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'submit'),
            handler: isset($data['handler']) ? (string) $data['handler'] : null,
            confirmMessage: isset($data['confirm_message']) ? (string) $data['confirm_message'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'handler' => $this->handler,
            'confirm_message' => $this->confirmMessage,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
