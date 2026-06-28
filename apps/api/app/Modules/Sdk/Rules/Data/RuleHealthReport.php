<?php

namespace App\Modules\Sdk\Rules\Data;

readonly class RuleHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public bool $healthy,
        public array $missingTables = [],
        public array $statistics = [],
        public array $warnings = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? $data['enabled'] ?? false),
            healthy: (bool) ($data['healthy'] ?? $data['healthy'] ?? false),
            missingTables: is_array($data['missing_tables'] ?? $data['missingTables'] ?? null) ? ($data['missing_tables'] ?? $data['missingTables']) : [],
            statistics: is_array($data['statistics'] ?? $data['statistics'] ?? null) ? ($data['statistics'] ?? $data['statistics']) : [],
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'healthy' => $this->healthy,
            'missing_tables' => $this->missingTables,
            'statistics' => $this->statistics,
            'warnings' => $this->warnings
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
