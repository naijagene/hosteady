<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationPreference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $channel,
        public string $type,
        public bool $enabled = true,
        public array $preferredChannels,
        public ?string $digestFrequency,
        public array $quietHours
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            channel: (string) ($data['channel'] ?? $data['channel'] ?? ''),
            type: (string) ($data['type'] ?? $data['type'] ?? ''),
            enabled: (bool) ($data['enabled'] ?? $data['enabled'] ?? false),
            preferredChannels: is_array($data['preferred_channels'] ?? $data['preferredChannels'] ?? null) ? ($data['preferred_channels'] ?? $data['preferredChannels']) : [],
            digestFrequency: isset($data['digest_frequency']) ? (string) $data['digest_frequency'] : (isset($data['digestFrequency']) ? (string) $data['digestFrequency'] : null),
            quietHours: is_array($data['quiet_hours'] ?? $data['quietHours'] ?? null) ? ($data['quiet_hours'] ?? $data['quietHours']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'channel' => $this->channel,
            'type' => $this->type,
            'enabled' => $this->enabled,
            'preferred_channels' => $this->preferredChannels,
            'digest_frequency' => $this->digestFrequency,
            'quiet_hours' => $this->quietHours
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
