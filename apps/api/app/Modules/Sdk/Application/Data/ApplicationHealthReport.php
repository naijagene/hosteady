<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public bool $healthy,
        public string $status,
        public int $registeredApps,
        public int $enabledApps,
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
            registeredApps: (int) ($data['registered_apps'] ?? $data['registeredApps'] ?? 0),
            enabledApps: (int) ($data['enabled_apps'] ?? $data['enabledApps'] ?? 0),
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
            'registered_apps' => $this->registeredApps,
            'enabled_apps' => $this->enabledApps,
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
