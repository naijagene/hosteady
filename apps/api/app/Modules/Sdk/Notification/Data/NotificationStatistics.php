<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationStatistics implements \JsonSerializable
{
    public function __construct(
        public int $notifications,
        public int $deliveries,
        public int $templates,
        public int $subscriptions,
        public int $schedules,
        public int $digests
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            notifications: (int) ($data['notifications'] ?? $data['notifications'] ?? 0),
            deliveries: (int) ($data['deliveries'] ?? $data['deliveries'] ?? 0),
            templates: (int) ($data['templates'] ?? $data['templates'] ?? 0),
            subscriptions: (int) ($data['subscriptions'] ?? $data['subscriptions'] ?? 0),
            schedules: (int) ($data['schedules'] ?? $data['schedules'] ?? 0),
            digests: (int) ($data['digests'] ?? $data['digests'] ?? 0)
        );
    }

    public function toArray(): array
    {
        return [
            'notifications' => $this->notifications,
            'deliveries' => $this->deliveries,
            'templates' => $this->templates,
            'subscriptions' => $this->subscriptions,
            'schedules' => $this->schedules,
            'digests' => $this->digests
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
