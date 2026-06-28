<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportLayout implements \JsonSerializable
{
    /**
     * @param  list<array<string, mixed>>  $sections
     */
    public function __construct(
        public array $sections = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sections: is_array($data['sections'] ?? null) ? $data['sections'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'sections' => $this->sections,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
