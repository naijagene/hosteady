<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowRollbackResult implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $installPublicId,
        public string $restoredVersion,
        public string $status,
        public ?string $installedWorkflowDefinitionPublicId = null,
        public array $warnings = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            installPublicId: (string) ($data['install_public_id'] ?? ''),
            restoredVersion: (string) ($data['restored_version'] ?? ''),
            status: (string) ($data['status'] ?? 'rolled_back'),
            installedWorkflowDefinitionPublicId: isset($data['installed_workflow_definition_public_id'])
                ? (string) $data['installed_workflow_definition_public_id']
                : null,
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'install_public_id' => $this->installPublicId,
            'restored_version' => $this->restoredVersion,
            'status' => $this->status,
            'installed_workflow_definition_public_id' => $this->installedWorkflowDefinitionPublicId,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
