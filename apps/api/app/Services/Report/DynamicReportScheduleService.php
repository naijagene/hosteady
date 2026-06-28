<?php

namespace App\Services\Report;

use App\Models\Organization;
use App\Models\ReportSchedule as ReportScheduleModel;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Modules\Sdk\Report\Contracts\ReportScheduler;
use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;
use App\Modules\Sdk\Report\Enums\ReportScheduleStatus;
use App\Modules\Sdk\Report\Exceptions\ReportNotFoundException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class DynamicReportScheduleService implements ReportScheduler
{
    public function __construct(
        private readonly DynamicReportRegistryService $registryService,
        private readonly DynamicReportAuditRecorder $auditRecorder,
        private readonly SchedulerPort $schedulerPort,
    ) {
    }

    public function create(ReportScheduleDefinition $schedule): ReportScheduleDefinition
    {
        $model = $this->registryService->findModel($schedule->moduleKey, $schedule->reportKey);

        if ($model === null) {
            throw new ReportNotFoundException(sprintf(
                'Report [%s.%s] was not found.',
                $schedule->moduleKey,
                $schedule->reportKey,
            ));
        }

        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        return DB::transaction(function () use ($schedule, $model, $context) {
            $scheduleModel = new ReportScheduleModel([
                'organization_id' => $context?->organization->id ?? $model->organization_id,
                'workspace_id' => $context?->workspace->id ?? $model->workspace_id,
                'report_definition_id' => $model->id,
                'name' => $schedule->name,
                'status' => ReportScheduleStatus::Active->value,
                'cron_expression' => $schedule->cronExpression,
                'run_at' => $schedule->runAt,
                'timezone' => $schedule->timezone,
                'export_formats_json' => $schedule->exportFormats,
                'recipients_json' => $schedule->recipients,
                'parameters_json' => $schedule->parameters,
                'metadata' => $schedule->metadata,
            ]);
            $scheduleModel->save();

            $this->syncScheduledTask($scheduleModel, $model->module_key, $model->report_key);
            $this->auditRecorder->recordScheduleCreated($schedule->moduleKey, $schedule->reportKey, $scheduleModel->public_id);

            return DynamicReportMapper::toSchedule($scheduleModel->fresh(), $model->module_key, $model->report_key);
        });
    }

    public function pause(string $schedulePublicId): ReportScheduleDefinition
    {
        $scheduleModel = $this->findScheduleModel($schedulePublicId);
        $definition = $scheduleModel->reportDefinition;

        $scheduleModel->update(['status' => ReportScheduleStatus::Paused->value]);
        $this->pauseScheduledTask($scheduleModel);
        $this->auditRecorder->recordScheduleUpdated($definition->module_key, $definition->report_key, $schedulePublicId);

        return DynamicReportMapper::toSchedule($scheduleModel->fresh(), $definition->module_key, $definition->report_key);
    }

    public function resume(string $schedulePublicId): ReportScheduleDefinition
    {
        $scheduleModel = $this->findScheduleModel($schedulePublicId);
        $definition = $scheduleModel->reportDefinition;

        $scheduleModel->update(['status' => ReportScheduleStatus::Active->value]);
        $this->syncScheduledTask($scheduleModel, $definition->module_key, $definition->report_key);
        $this->auditRecorder->recordScheduleUpdated($definition->module_key, $definition->report_key, $schedulePublicId);

        return DynamicReportMapper::toSchedule($scheduleModel->fresh(), $definition->module_key, $definition->report_key);
    }

    public function delete(string $schedulePublicId): void
    {
        $scheduleModel = $this->findScheduleModel($schedulePublicId);
        $definition = $scheduleModel->reportDefinition;

        DB::transaction(function () use ($scheduleModel, $definition, $schedulePublicId) {
            $this->pauseScheduledTask($scheduleModel);
            $scheduleModel->update(['status' => ReportScheduleStatus::Deleted->value]);
            $scheduleModel->delete();
            $this->auditRecorder->recordScheduleDeleted($definition->module_key, $definition->report_key, $schedulePublicId);
        });
    }

    /**
     * @return list<ReportScheduleDefinition>
     */
    public function list(string $moduleKey, string $reportKey): array
    {
        $model = $this->registryService->findModel($moduleKey, $reportKey);

        if ($model === null) {
            return [];
        }

        return ReportScheduleModel::query()
            ->where('report_definition_id', $model->id)
            ->where('status', '!=', ReportScheduleStatus::Deleted->value)
            ->orderBy('name')
            ->get()
            ->map(fn (ReportScheduleModel $schedule) => DynamicReportMapper::toSchedule($schedule, $moduleKey, $reportKey))
            ->all();
    }

    private function findScheduleModel(string $schedulePublicId): ReportScheduleModel
    {
        $schedule = ReportScheduleModel::query()
            ->with('reportDefinition')
            ->where('public_id', $schedulePublicId)
            ->first();

        if ($schedule === null) {
            throw new ReportNotFoundException(sprintf('Report schedule [%s] was not found.', $schedulePublicId));
        }

        return $schedule;
    }

    private function syncScheduledTask(ReportScheduleModel $schedule, string $moduleKey, string $reportKey): void
    {
        if ($schedule->status !== ReportScheduleStatus::Active->value) {
            return;
        }

        try {
            $organization = Organization::query()->find($schedule->organization_id);
            $workspace = $schedule->workspace_id !== null
                ? Workspace::query()->find($schedule->workspace_id)
                : null;

            if ($organization === null) {
                return;
            }

            $scope = new EnterpriseScope(
                organizationPublicId: $organization->public_id,
                workspacePublicId: $workspace?->public_id,
                moduleKey: $moduleKey,
            );

            $metadata = is_array($schedule->metadata) ? $schedule->metadata : [];
            $existingPublicId = $metadata['scheduled_task_public_id'] ?? null;

            if (is_string($existingPublicId) && $existingPublicId !== '') {
                $existing = $this->schedulerPort->find($scope, $existingPublicId);
                if ($existing !== null) {
                    $this->schedulerPort->resume($scope, $existingPublicId);

                    return;
                }
            }

            $reference = $this->schedulerPort->create(new ScheduledTaskRequest(
                scope: $scope,
                taskType: 'report.scheduled.run',
                displayName: sprintf('Report schedule: %s', $schedule->name),
                description: sprintf('Scheduled report run for [%s.%s]', $moduleKey, $reportKey),
                cronExpression: $schedule->cron_expression ?? '* * * * *',
                runAt: $schedule->run_at?->toIso8601String(),
                timezone: $schedule->timezone ?? 'UTC',
                payload: [
                    'schedule_public_id' => $schedule->public_id,
                    'module_key' => $moduleKey,
                    'report_key' => $reportKey,
                ],
                entityReference: new EntityReference(
                    type: 'report_schedule',
                    publicId: $schedule->public_id,
                    moduleKey: $moduleKey,
                    label: $schedule->name,
                ),
                enabled: true,
            ));

            $metadata['scheduled_task_public_id'] = $reference->publicId;
            $schedule->update(['metadata' => $metadata]);
        } catch (\Throwable) {
        }
    }

    private function pauseScheduledTask(ReportScheduleModel $schedule): void
    {
        try {
            $metadata = is_array($schedule->metadata) ? $schedule->metadata : [];
            $taskPublicId = $metadata['scheduled_task_public_id'] ?? null;

            if (! is_string($taskPublicId) || $taskPublicId === '') {
                return;
            }

            $organization = Organization::query()->find($schedule->organization_id);
            if ($organization === null) {
                return;
            }

            $workspace = $schedule->workspace_id !== null
                ? Workspace::query()->find($schedule->workspace_id)
                : null;

            $scope = new EnterpriseScope(
                organizationPublicId: $organization->public_id,
                workspacePublicId: $workspace?->public_id,
                moduleKey: $schedule->reportDefinition?->module_key ?? 'reports',
            );

            $this->schedulerPort->pause($scope, $taskPublicId);
        } catch (\Throwable) {
        }
    }
}
