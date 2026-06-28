<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationDigest implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $frequency = 'daily',
        public string $status = 'pending',
        public int $notificationCount,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            frequency: (string) ($data['frequency'] ?? $data['frequency'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            notificationCount: (int) ($data['notification_count'] ?? $data['notificationCount'] ?? 0),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'notification_count' => $this->notificationCount,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
