<?php

namespace App\Modules\Sdk\Entity\Data;

use App\Modules\Sdk\Entity\Enums\EntityOwnershipScope;
use App\Modules\Sdk\Entity\Enums\EntityStatus;
use App\Modules\Sdk\Entity\Enums\EntityVisibility;

readonly class EntityFieldDefinition implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $required = false,
        public bool $searchable = false,
        public bool $auditable = true,
        public ?string $description = null,
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'string'),
            required: (bool) ($data['required'] ?? false),
            searchable: (bool) ($data['searchable'] ?? false),
            auditable: (bool) ($data['auditable'] ?? true),
            description: isset($data['description']) ? (string) $data['description'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'searchable' => $this->searchable,
            'auditable' => $this->auditable,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
