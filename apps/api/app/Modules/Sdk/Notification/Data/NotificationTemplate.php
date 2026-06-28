<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationTemplate implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $moduleKey,
        public string $type,
        public string $templateType = 'module',
        public ?string $subject,
        public string $body,
        public array $channels,
        public array $variables,
        public string $scope = 'organization'
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? $data['publicId'] ?? ''),
            moduleKey: (string) ($data['module_key'] ?? $data['moduleKey'] ?? ''),
            type: (string) ($data['type'] ?? $data['type'] ?? ''),
            templateType: (string) ($data['template_type'] ?? $data['templateType'] ?? ''),
            subject: isset($data['subject']) ? (string) $data['subject'] : (isset($data['subject']) ? (string) $data['subject'] : null),
            body: (string) ($data['body'] ?? $data['body'] ?? ''),
            channels: is_array($data['channels'] ?? $data['channels'] ?? null) ? ($data['channels'] ?? $data['channels']) : [],
            variables: is_array($data['variables'] ?? $data['variables'] ?? null) ? ($data['variables'] ?? $data['variables']) : [],
            scope: (string) ($data['scope'] ?? $data['scope'] ?? '')
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'module_key' => $this->moduleKey,
            'type' => $this->type,
            'template_type' => $this->templateType,
            'subject' => $this->subject,
            'body' => $this->body,
            'channels' => $this->channels,
            'variables' => $this->variables,
            'scope' => $this->scope
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
