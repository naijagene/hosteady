<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $title,
        public string $body,
        public string $status = 'pending',
        public string $priority = 'normal',
        public string $scope = 'user',
        public ?string $templateKey,
        public array $channels,
        public array $mergeData,
        public array $metadata,
        public ?string $readAt,
        public ?string $createdAt
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            title: (string) ($data['title'] ?? $data['title'] ?? ''),
            body: (string) ($data['body'] ?? $data['body'] ?? ''),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            priority: (string) ($data['priority'] ?? $data['priority'] ?? ''),
            scope: (string) ($data['scope'] ?? $data['scope'] ?? ''),
            templateKey: isset($data['template_key']) ? (string) $data['template_key'] : (isset($data['templateKey']) ? (string) $data['templateKey'] : null),
            channels: is_array($data['channels'] ?? $data['channels'] ?? null) ? ($data['channels'] ?? $data['channels']) : [],
            mergeData: is_array($data['merge_data'] ?? $data['mergeData'] ?? null) ? ($data['merge_data'] ?? $data['mergeData']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
            readAt: isset($data['read_at']) ? (string) $data['read_at'] : (isset($data['readAt']) ? (string) $data['readAt'] : null),
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : (isset($data['createdAt']) ? (string) $data['createdAt'] : null)
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'priority' => $this->priority,
            'scope' => $this->scope,
            'template_key' => $this->templateKey,
            'channels' => $this->channels,
            'merge_data' => $this->mergeData,
            'metadata' => $this->metadata,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
