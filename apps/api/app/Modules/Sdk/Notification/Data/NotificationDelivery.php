<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationDelivery implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $notificationPublicId,
        public string $channel,
        public string $status = 'pending',
        public string $recipientMembershipPublicId,
        public ?string $deliveredAt,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            notificationPublicId: (string) ($data['notification_public_id'] ?? $data['notificationPublicId'] ?? ''),
            channel: (string) ($data['channel'] ?? $data['channel'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            recipientMembershipPublicId: (string) ($data['recipient_membership_public_id'] ?? $data['recipientMembershipPublicId'] ?? ''),
            deliveredAt: isset($data['delivered_at']) ? (string) $data['delivered_at'] : (isset($data['deliveredAt']) ? (string) $data['deliveredAt'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'notification_public_id' => $this->notificationPublicId,
            'channel' => $this->channel,
            'status' => $this->status,
            'recipient_membership_public_id' => $this->recipientMembershipPublicId,
            'delivered_at' => $this->deliveredAt,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
