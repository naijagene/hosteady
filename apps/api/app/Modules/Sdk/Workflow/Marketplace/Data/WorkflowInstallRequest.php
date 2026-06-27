<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowInstallRequest implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $packagePublicId,
        public ?string $versionPublicId = null,
        public ?string $targetVersion = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            packagePublicId: (string) ($data['package_public_id'] ?? ''),
            versionPublicId: isset($data['version_public_id']) ? (string) $data['version_public_id'] : null,
            targetVersion: isset($data['target_version']) ? (string) $data['target_version'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'package_public_id' => $this->packagePublicId,
            'version_public_id' => $this->versionPublicId,
            'target_version' => $this->targetVersion,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
