<?php

namespace App\Modules\Sdk\Form\Data;

use App\Modules\Sdk\Form\Enums\FormLayoutType;

readonly class FormLayout implements \JsonSerializable
{
    public function __construct(
        public string $type = FormLayoutType::Default->value,
        public int $columns = 1,
        public ?string $spacing = null,
        public array $config = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? FormLayoutType::Default->value),
            columns: (int) ($data['columns'] ?? 1),
            spacing: isset($data['spacing']) ? (string) $data['spacing'] : null,
            config: is_array($data['config'] ?? null) ? $data['config'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'columns' => $this->columns,
            'spacing' => $this->spacing,
            'config' => $this->config,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
