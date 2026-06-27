<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowInstallResult implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $installPublicId,
        public string $packagePublicId,
        public string $packageVersionPublicId,
        public string $installedVersion,
        public string $status,
        public ?string $installedWorkflowDefinitionPublicId = null,
        public array $warnings = [],
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            installPublicId: (string) ($data['install_public_id'] ?? ''),
            packagePublicId: (string) ($data['package_public_id'] ?? ''),
            packageVersionPublicId: (string) ($data['package_version_public_id'] ?? ''),
            installedVersion: (string) ($data['installed_version'] ?? ''),
            status: (string) ($data['status'] ?? 'installed'),
            installedWorkflowDefinitionPublicId: isset($data['installed_workflow_definition_public_id'])
                ? (string) $data['installed_workflow_definition_public_id']
                : null,
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'install_public_id' => $this->installPublicId,
            'package_public_id' => $this->packagePublicId,
            'package_version_public_id' => $this->packageVersionPublicId,
            'installed_version' => $this->installedVersion,
            'status' => $this->status,
            'installed_workflow_definition_public_id' => $this->installedWorkflowDefinitionPublicId,
            'warnings' => $this->warnings,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
