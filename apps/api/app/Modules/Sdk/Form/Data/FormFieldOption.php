<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormFieldOption implements \JsonSerializable
{
    public function __construct(
        public string $value,
        public string $label,
        public ?string $description = null,
        public bool $disabled = false,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            value: (string) ($data['value'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            disabled: (bool) ($data['disabled'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'description' => $this->description,
            'disabled' => $this->disabled,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
