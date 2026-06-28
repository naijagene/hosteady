<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordProjection implements \JsonSerializable
{
    /**
     * @param  list<string>  $fields
     */
    public function __construct(
        public array $fields = [],
        public bool $includeMetadata = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fields: is_array($data['fields'] ?? null) ? array_values(array_map('strval', $data['fields'])) : [],
            includeMetadata: (bool) ($data['include_metadata'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'fields' => $this->fields,
            'include_metadata' => $this->includeMetadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
