<?php

namespace App\Services\Report;

use App\Models\ReportActivityLog;
use App\Models\ReportDefinition as ReportDefinitionModel;

class DynamicReportActivityService
{
    public function __construct(
        private readonly DynamicReportAuditRecorder $auditRecorder,
    ) {
    }

    public function log(
        string $action,
        ?string $reportDefinitionId = null,
        ?string $organizationId = null,
        ?string $workspaceId = null,
        array $beforeState = [],
        array $afterState = [],
        array $metadata = [],
    ): void {
        try {
            ReportActivityLog::query()->create([
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'report_definition_id' => $reportDefinitionId,
                'action' => $action,
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            $this->auditRecorder->recordActivityLogged($action, $reportDefinitionId);
        } catch (\Throwable) {
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForReport(
        string $organizationId,
        ?string $workspaceId,
        string $reportPublicId,
    ): array {
        $definition = ReportDefinitionModel::query()->where('public_id', $reportPublicId)->first();

        if ($definition === null) {
            return [];
        }

        return ReportActivityLog::query()
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->where('report_definition_id', $definition->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReportActivityLog $log) => DynamicReportMapper::toActivityReference($log))
            ->all();
    }
}
