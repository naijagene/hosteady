<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordHealthReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $missingTables
     */
    public function __construct(
        public bool $enabled,
        public int $records,
        public int $versions,
        public int $links,
        public int $activityLogs,
        public array $warnings = [],
        public string $status = 'healthy',
        public array $missingTables = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            records: (int) ($data['records'] ?? 0),
            versions: (int) ($data['versions'] ?? 0),
            links: (int) ($data['links'] ?? 0),
            activityLogs: (int) ($data['activity_logs'] ?? 0),
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            status: (string) ($data['status'] ?? 'healthy'),
            missingTables: is_array($data['missing_tables'] ?? null) ? array_values(array_map('strval', $data['missing_tables'])) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'records' => $this->records,
            'versions' => $this->versions,
            'links' => $this->links,
            'activity_logs' => $this->activityLogs,
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
