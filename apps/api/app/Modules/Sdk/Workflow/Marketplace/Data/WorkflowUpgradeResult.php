<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowUpgradeResult implements \JsonSerializable
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $installPublicId,
        public string $previousVersion,
        public string $installedVersion,
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
            previousVersion: (string) ($data['previous_version'] ?? ''),
            installedVersion: (string) ($data['installed_version'] ?? ''),
            status: (string) ($data['status'] ?? 'installed'),
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
            'previous_version' => $this->previousVersion,
            'installed_version' => $this->installedVersion,
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
