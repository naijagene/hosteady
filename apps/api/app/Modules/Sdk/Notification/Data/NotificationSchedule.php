<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationSchedule implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $title,
        public string $cronExpression,
        public ?string $templateKey,
        public string $status = 'active',
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            title: (string) ($data['title'] ?? $data['title'] ?? ''),
            cronExpression: (string) ($data['cron_expression'] ?? $data['cronExpression'] ?? ''),
            templateKey: isset($data['template_key']) ? (string) $data['template_key'] : (isset($data['templateKey']) ? (string) $data['templateKey'] : null),
            status: (string) ($data['status'] ?? $data['status'] ?? ''),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'title' => $this->title,
            'cron_expression' => $this->cronExpression,
            'template_key' => $this->templateKey,
            'status' => $this->status,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
