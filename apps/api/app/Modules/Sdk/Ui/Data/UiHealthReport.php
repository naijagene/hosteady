<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public bool $healthy,
        public string $status,
        public int $pages,
        public int $layouts,
        public int $components,
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
            pages: (int) ($data['pages'] ?? $data['pages'] ?? 0),
            layouts: (int) ($data['layouts'] ?? $data['layouts'] ?? 0),
            components: (int) ($data['components'] ?? $data['components'] ?? 0),
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
            'pages' => $this->pages,
            'layouts' => $this->layouts,
            'components' => $this->components,
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
