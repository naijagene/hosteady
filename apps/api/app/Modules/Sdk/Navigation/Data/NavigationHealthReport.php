<?php

namespace App\Modules\Sdk\Navigation\Data;

readonly class NavigationHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public bool $healthy,
        public string $status,
        public int $definitions,
        public int $versions,
        public int $items,
        public int $personalizations,
        public array $warnings,
        public array $missingTables,
        public array $statistics
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? $data['enabled'] ?? false),
            healthy: (bool) ($data['healthy'] ?? $data['healthy'] ?? false),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            definitions: (int) ($data['definitions'] ?? $data['definitions'] ?? 0),
            versions: (int) ($data['versions'] ?? $data['versions'] ?? 0),
            items: (int) ($data['items'] ?? $data['items'] ?? 0),
            personalizations: (int) ($data['personalizations'] ?? $data['personalizations'] ?? 0),
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
            missingTables: is_array($data['missing_tables'] ?? $data['missingTables'] ?? null) ? ($data['missing_tables'] ?? $data['missingTables']) : [],
            statistics: is_array($data['statistics'] ?? $data['statistics'] ?? null) ? ($data['statistics'] ?? $data['statistics']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'healthy' => $this->healthy,
            'status' => $this->status,
            'definitions' => $this->definitions,
            'versions' => $this->versions,
            'items' => $this->items,
            'personalizations' => $this->personalizations,
            'warnings' => $this->warnings,
            'missing_tables' => $this->missingTables,
            'statistics' => $this->statistics,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
