<?php

namespace App\Modules\Sdk\Ui\Data;

readonly class UiRenderContext implements \JsonSerializable
{
    public function __construct(
        public string $organizationPublicId,
        public ?string $workspacePublicId,
        public ?string $membershipPublicId,
        public ?string $moduleKey,
        public ?string $pageKey,
        public ?string $applicationPublicId,
        public array $capabilities,
        public array $metadata
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            organizationPublicId: (string) ($data['organization_public_id'] ?? $data['organizationPublicId'] ?? ''),
            workspacePublicId: isset($data['workspace_public_id']) ? (string) $data['workspace_public_id'] : (isset($data['workspacePublicId']) ? (string) $data['workspacePublicId'] : null),
            membershipPublicId: isset($data['membership_public_id']) ? (string) $data['membership_public_id'] : (isset($data['membershipPublicId']) ? (string) $data['membershipPublicId'] : null),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : (isset($data['moduleKey']) ? (string) $data['moduleKey'] : null),
            pageKey: isset($data['page_key']) ? (string) $data['page_key'] : (isset($data['pageKey']) ? (string) $data['pageKey'] : null),
            applicationPublicId: isset($data['application_public_id']) ? (string) $data['application_public_id'] : (isset($data['applicationPublicId']) ? (string) $data['applicationPublicId'] : null),
            capabilities: is_array($data['capabilities'] ?? $data['capabilities'] ?? null) ? ($data['capabilities'] ?? $data['capabilities']) : [],
            metadata: is_array($data['metadata'] ?? $data['metadata'] ?? null) ? ($data['metadata'] ?? $data['metadata']) : [],
        );
    }

    public function toArray(): array
    {
        return [
            'organization_public_id' => $this->organizationPublicId,
            'workspace_public_id' => $this->workspacePublicId,
            'membership_public_id' => $this->membershipPublicId,
            'module_key' => $this->moduleKey,
            'page_key' => $this->pageKey,
            'application_public_id' => $this->applicationPublicId,
            'capabilities' => $this->capabilities,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
