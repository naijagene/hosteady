<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationHealthReport implements \JsonSerializable
{
    public function __construct(
        public bool $enabled,
        public int $notifications,
        public int $deliveries,
        public array $warnings,
        public string $status = 'healthy',
        public array $missingTables
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? $data['enabled'] ?? false),
            notifications: (int) ($data['notifications'] ?? $data['notifications'] ?? 0),
            deliveries: (int) ($data['deliveries'] ?? $data['deliveries'] ?? 0),
            warnings: is_array($data['warnings'] ?? $data['warnings'] ?? null) ? ($data['warnings'] ?? $data['warnings']) : [],
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            missingTables: is_array($data['missing_tables'] ?? $data['missingTables'] ?? null) ? ($data['missing_tables'] ?? $data['missingTables']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'notifications' => $this->notifications,
            'deliveries' => $this->deliveries,
            'warnings' => $this->warnings,
            'status' => $this->status,
            'missing_tables' => $this->missingTables
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
