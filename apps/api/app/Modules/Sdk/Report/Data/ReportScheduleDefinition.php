<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportScheduleDefinition implements \JsonSerializable
{
    public function __construct(
        public string $moduleKey,
        public string $reportKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $reportDefinitionId = null,
        public ?string $cronExpression = null,
        public ?string $runAt = null,
        public string $timezone = 'UTC',
        public string $status = 'active',
        public array $exportFormats = [],
        public array $recipients = [],
        public array $parameters = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            reportKey: (string) ($data['report_key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            reportDefinitionId: isset($data['report_definition_id']) ? (string) $data['report_definition_id'] : null,
            cronExpression: isset($data['cron_expression']) ? (string) $data['cron_expression'] : null,
            runAt: isset($data['run_at']) ? (string) $data['run_at'] : null,
            timezone: (string) ($data['timezone'] ?? 'UTC'),
            status: (string) ($data['status'] ?? 'active'),
            exportFormats: is_array($data['export_formats'] ?? $data['export_formats_json'] ?? null)
                ? ($data['export_formats'] ?? $data['export_formats_json'])
                : [],
            recipients: is_array($data['recipients'] ?? $data['recipients_json'] ?? null)
                ? ($data['recipients'] ?? $data['recipients_json'])
                : [],
            parameters: is_array($data['parameters'] ?? null) ? $data['parameters'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'report_definition_id' => $this->reportDefinitionId,
            'module_key' => $this->moduleKey,
            'report_key' => $this->reportKey,
            'name' => $this->name,
            'cron_expression' => $this->cronExpression,
            'run_at' => $this->runAt,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'export_formats' => $this->exportFormats,
            'recipients' => $this->recipients,
            'parameters' => $this->parameters,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
