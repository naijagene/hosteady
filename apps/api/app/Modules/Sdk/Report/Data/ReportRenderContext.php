<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportRenderContext implements \JsonSerializable
{
    public function __construct(
        public ?string $organizationPublicId = null,
        public ?string $workspacePublicId = null,
        public ?string $userPublicId = null,
        public array $parameters = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            organizationPublicId: isset($data['organization_public_id']) ? (string) $data['organization_public_id'] : null,
            workspacePublicId: isset($data['workspace_public_id']) ? (string) $data['workspace_public_id'] : null,
            userPublicId: isset($data['user_public_id']) ? (string) $data['user_public_id'] : null,
            parameters: is_array($data['parameters'] ?? null) ? $data['parameters'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'organization_public_id' => $this->organizationPublicId,
            'workspace_public_id' => $this->workspacePublicId,
            'user_public_id' => $this->userPublicId,
            'parameters' => $this->parameters,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
