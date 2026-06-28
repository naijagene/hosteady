<?php

namespace App\Services\Report;

use App\Models\ReportDefinition as ReportDefinitionModel;
use App\Models\ReportRun as ReportRunModel;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportRunResult;
use App\Modules\Sdk\Report\Enums\ReportRunStatus;
use App\Modules\Sdk\Report\Exceptions\ReportNotFoundException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class DynamicReportRunService
{
    public function __construct(
        private readonly DynamicReportRegistryService $registryService,
        private readonly DynamicReportRendererService $rendererService,
        private readonly DynamicReportAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function start(
        string $moduleKey,
        string $reportKey,
        array $parameters = [],
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): ReportRunResult {
        $definition = $this->registryService->find($moduleKey, $reportKey);

        if ($definition === null) {
            throw new ReportNotFoundException(sprintf('Report [%s.%s] was not found.', $moduleKey, $reportKey));
        }

        $model = $this->registryService->findModel($moduleKey, $reportKey);

        return DB::transaction(function () use ($definition, $model, $parameters, $organizationId, $workspaceId) {
            $run = new ReportRunModel([
                'organization_id' => $organizationId ?? $model?->organization_id,
                'workspace_id' => $workspaceId ?? $model?->workspace_id,
                'report_definition_id' => $model?->id,
                'status' => ReportRunStatus::Running->value,
                'parameters_json' => $parameters,
                'started_at' => now(),
            ]);
            $run->save();

            $this->auditRecorder->recordRunStarted($definition, $run->public_id);

            try {
                $rendered = $this->rendererService->render($definition, ['parameters' => $parameters]);
                $completedAt = now();
                $durationMs = $run->started_at !== null
                    ? (int) $run->started_at->diffInMilliseconds($completedAt)
                    : null;

                $run->update([
                    'status' => ReportRunStatus::Completed->value,
                    'result_json' => $rendered,
                    'completed_at' => $completedAt,
                    'duration_ms' => $durationMs,
                ]);

                $this->auditRecorder->recordRunCompleted($definition, $run->public_id);
            } catch (\Throwable $exception) {
                $run->update([
                    'status' => ReportRunStatus::Failed->value,
                    'result_json' => ['error' => $exception->getMessage()],
                    'completed_at' => now(),
                ]);

                $this->auditRecorder->recordRunFailed($definition, $run->public_id);

                throw $exception;
            }

            return DynamicReportMapper::toRunResult($run->fresh());
        });
    }

    public function findByPublicId(string $publicId): ?ReportRunResult
    {
        $run = ReportRunModel::query()->where('public_id', $publicId)->first();

        return $run === null ? null : DynamicReportMapper::toRunResult($run);
    }

    /**
     * @return list<ReportRunResult>
     */
    public function listForReport(string $moduleKey, string $reportKey): array
    {
        $model = $this->registryService->findModel($moduleKey, $reportKey);

        if ($model === null) {
            return [];
        }

        return ReportRunModel::query()
            ->where('report_definition_id', $model->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReportRunModel $run) => DynamicReportMapper::toRunResult($run))
            ->all();
    }
}
