<?php

namespace App\Modules\Sdk\Integration\Data;

readonly class IntegrationHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public bool $healthy,
        public string $status,
        public int $events,
        public int $subscriptions,
        public int $connectors,
        public int $endpoints,
        public int $dispatches,
        public int $deadLetters,
        public array $missingTables,
        public array $warnings,
        public array $statistics,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? $data['Enabled'] ?? false),
            healthy: (bool) ($data['healthy'] ?? $data['Healthy'] ?? false),
            status: (string) ($data['status'] ?? $data['Status'] ?? ''),
            events: (int) ($data['events'] ?? $data['Events'] ?? 0),
            subscriptions: (int) ($data['subscriptions'] ?? $data['Subscriptions'] ?? 0),
            connectors: (int) ($data['connectors'] ?? $data['Connectors'] ?? 0),
            endpoints: (int) ($data['endpoints'] ?? $data['Endpoints'] ?? 0),
            dispatches: (int) ($data['dispatches'] ?? $data['Dispatches'] ?? 0),
            deadLetters: (int) ($data['dead_letters'] ?? $data['DeadLetters'] ?? 0),
            missingTables: is_array($data['missing_tables'] ?? $data['MissingTables'] ?? null) ? ($data['missing_tables'] ?? $data['MissingTables']) : [],
            warnings: is_array($data['warnings'] ?? $data['Warnings'] ?? null) ? ($data['warnings'] ?? $data['Warnings']) : [],
            statistics: is_array($data['statistics'] ?? $data['Statistics'] ?? null) ? ($data['statistics'] ?? $data['Statistics']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'healthy' => $this->healthy,
            'status' => $this->status,
            'events' => $this->events,
            'subscriptions' => $this->subscriptions,
            'connectors' => $this->connectors,
            'endpoints' => $this->endpoints,
            'dispatches' => $this->dispatches,
            'dead_letters' => $this->deadLetters,
            'missing_tables' => $this->missingTables,
            'warnings' => $this->warnings,
            'statistics' => $this->statistics,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
