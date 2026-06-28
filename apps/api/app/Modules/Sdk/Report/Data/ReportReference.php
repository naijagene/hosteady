<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportReference implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $reportKey,
        public ?string $publicId = null,
        public ?string $entityKey = null,
        public ?string $tableKey = null,
        public ?string $dashboardKey = null,
        public ?string $label = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            reportKey: (string) ($data['report_key'] ?? $data['key'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            tableKey: isset($data['table_key']) ? (string) $data['table_key'] : null,
            dashboardKey: isset($data['dashboard_key']) ? (string) $data['dashboard_key'] : null,
            label: isset($data['label']) ? (string) $data['label'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'report_key' => $this->reportKey,
            'public_id' => $this->publicId,
            'entity_key' => $this->entityKey,
            'table_key' => $this->tableKey,
            'dashboard_key' => $this->dashboardKey,
            'label' => $this->label,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
