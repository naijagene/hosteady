<?php

namespace App\Modules\Sdk\Notification\Data;

readonly class NotificationMessage implements \JsonSerializable
{
    public function __construct(
        public string $title,
        public string $body,
        public string $scope = 'user',
        public string $priority = 'normal',
        public ?string $templateKey,
        public array $mergeData,
        public array $channels,
        public ?string $recipientMembershipPublicId,
        public array $recipientMembershipPublicIds,
        public ?string $rolePublicId,
        public ?string $moduleKey,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? $data['title'] ?? ''),
            body: (string) ($data['body'] ?? $data['body'] ?? ''),
            scope: (string) ($data['scope'] ?? $data['scope'] ?? ''),
            priority: (string) ($data['priority'] ?? $data['priority'] ?? ''),
            templateKey: isset($data['template_key']) ? (string) $data['template_key'] : (isset($data['templateKey']) ? (string) $data['templateKey'] : null),
            mergeData: is_array($data['merge_data'] ?? $data['mergeData'] ?? null) ? ($data['merge_data'] ?? $data['mergeData']) : [],
            channels: is_array($data['channels'] ?? $data['channels'] ?? null) ? ($data['channels'] ?? $data['channels']) : [],
            recipientMembershipPublicId: isset($data['recipient_membership_public_id']) ? (string) $data['recipient_membership_public_id'] : (isset($data['recipientMembershipPublicId']) ? (string) $data['recipientMembershipPublicId'] : null),
            recipientMembershipPublicIds: is_array($data['recipient_membership_public_ids'] ?? $data['recipientMembershipPublicIds'] ?? null) ? ($data['recipient_membership_public_ids'] ?? $data['recipientMembershipPublicIds']) : [],
            rolePublicId: isset($data['role_public_id']) ? (string) $data['role_public_id'] : (isset($data['rolePublicId']) ? (string) $data['rolePublicId'] : null),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : []
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'scope' => $this->scope,
            'priority' => $this->priority,
            'template_key' => $this->templateKey,
            'merge_data' => $this->mergeData,
            'channels' => $this->channels,
            'recipient_membership_public_id' => $this->recipientMembershipPublicId,
            'recipient_membership_public_ids' => $this->recipientMembershipPublicIds,
            'role_public_id' => $this->rolePublicId,
            'module_key' => $this->moduleKey,
            'metadata' => $this->metadata
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
