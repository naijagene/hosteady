<?php

namespace App\Modules\Sdk\DataRepository\Data;

readonly class EntityRecordStatistics implements \JsonSerializable
{
    public function __construct(
        public int $records,
        public int $versions,
        public int $links,
        public int $activityLogs,
        public array $recordsByEntity = [],
        public array $registeredModules = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            records: (int) ($data['records'] ?? 0),
            versions: (int) ($data['versions'] ?? 0),
            links: (int) ($data['links'] ?? 0),
            activityLogs: (int) ($data['activity_logs'] ?? 0),
            recordsByEntity: is_array($data['records_by_entity'] ?? null) ? $data['records_by_entity'] : [],
            registeredModules: is_array($data['registered_modules'] ?? null) ? $data['registered_modules'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'records' => $this->records,
            'versions' => $this->versions,
            'links' => $this->links,
            'activity_logs' => $this->activityLogs,
            'records_by_entity' => $this->recordsByEntity,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
