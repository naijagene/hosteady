<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityHealthReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $missingTables
     */
    public function __construct(
        public bool $enabled,
        public int $definitions,
        public int $relationships,
        public int $comments,
        public int $tags,
        public array $warnings = [],
        public string $status = 'healthy',
        public array $missingTables = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            definitions: (int) ($data['definitions'] ?? 0),
            relationships: (int) ($data['relationships'] ?? 0),
            comments: (int) ($data['comments'] ?? 0),
            tags: (int) ($data['tags'] ?? 0),
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            status: (string) ($data['status'] ?? 'healthy'),
            missingTables: is_array($data['missing_tables'] ?? null) ? array_values(array_map('strval', $data['missing_tables'])) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'definitions' => $this->definitions,
            'relationships' => $this->relationships,
            'comments' => $this->comments,
            'tags' => $this->tags,
            'warnings' => $this->warnings,
            'status' => $this->status,
            'missing_tables' => $this->missingTables,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
