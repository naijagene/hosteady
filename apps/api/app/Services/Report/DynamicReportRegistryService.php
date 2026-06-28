<?php

namespace App\Services\Report;

use App\Models\ReportDefinition as ReportDefinitionModel;
use App\Modules\Sdk\Report\Contracts\ReportRegistry;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Exceptions\ReportNotFoundException;
use App\Modules\Sdk\Report\Exceptions\ReportRegistryException;
use Illuminate\Support\Facades\DB;

class DynamicReportRegistryService implements ReportRegistry
{
    public function __construct(
        private readonly DynamicReportValidationService $validator,
        private readonly DynamicReportAuditRecorder $auditRecorder,
        private readonly DynamicReportSearchIndexer $searchIndexer,
        private readonly DynamicReportWorkflowBridge $workflowBridge,
    ) {
    }

    public function register(mixed $source): ReportDefinition
    {
        $definition = $this->resolveDefinitionSource($source);
        $this->validator->assertValid($definition);

        if (ReportDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('report_key', $definition->reportKey)
            ->whereNull('organization_id')
            ->whereNull('workspace_id')
            ->exists()) {
            throw new ReportRegistryException(sprintf(
                'Report definition [%s.%s] is already registered.',
                $definition->moduleKey,
                $definition->reportKey,
            ));
        }

        return DB::transaction(function () use ($definition) {
            $model = new ReportDefinitionModel;
            DynamicReportMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionRegistered($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);
            $this->workflowBridge->triggerDefinitionRegisteredBestEffort($model);

            return DynamicReportMapper::toDefinition($model->fresh());
        });
    }

    public function update(ReportDefinition $definition): ReportDefinition
    {
        $this->validator->assertValid($definition);

        $model = ReportDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('report_key', $definition->reportKey)
            ->first();

        if ($model === null) {
            throw new ReportNotFoundException(sprintf(
                'Report definition [%s.%s] was not found.',
                $definition->moduleKey,
                $definition->reportKey,
            ));
        }

        return DB::transaction(function () use ($model, $definition) {
            DynamicReportMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionUpdated($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);
            $this->workflowBridge->triggerDefinitionUpdatedBestEffort($model);

            return DynamicReportMapper::toDefinition($model->fresh());
        });
    }

    public function find(string $moduleKey, string $reportKey): ?ReportDefinition
    {
        $model = ReportDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('report_key', $reportKey)
            ->first();

        return $model === null ? null : DynamicReportMapper::toDefinition($model);
    }

    public function findByPublicId(string $publicId): ?ReportDefinition
    {
        $model = ReportDefinitionModel::query()->where('public_id', $publicId)->first();

        return $model === null ? null : DynamicReportMapper::toDefinition($model);
    }

    /**
     * @return list<ReportDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        $query = ReportDefinitionModel::query()->orderBy('module_key')->orderBy('report_key');

        if ($moduleKey !== null) {
            $query->where('module_key', $moduleKey);
        }

        return $query->get()
            ->map(fn (ReportDefinitionModel $model) => DynamicReportMapper::toDefinition($model))
            ->all();
    }

    /**
     * @return list<ReportDefinition>
     */
    public function findByEntity(string $moduleKey, string $entityKey): array
    {
        return ReportDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->orderBy('report_key')
            ->get()
            ->map(fn (ReportDefinitionModel $model) => DynamicReportMapper::toDefinition($model))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $reports
     * @return list<ReportDefinition>
     */
    public function registerFromManifestReports(array $reports, string $moduleKey): array
    {
        $registered = [];

        foreach ($reports as $report) {
            if (! is_array($report)) {
                continue;
            }

            $payload = array_merge($report, ['module_key' => $moduleKey]);
            $reportKey = (string) ($payload['report_key'] ?? $payload['key'] ?? '');

            if ($reportKey === '') {
                continue;
            }

            if ($this->find($moduleKey, $reportKey) !== null) {
                continue;
            }

            $registered[] = $this->register(ReportDefinition::fromArray($payload));
        }

        return $registered;
    }

    public function findModel(string $moduleKey, string $reportKey): ?ReportDefinitionModel
    {
        return ReportDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('report_key', $reportKey)
            ->first();
    }

    private function resolveDefinitionSource(mixed $source): ReportDefinition
    {
        if ($source instanceof ReportDefinition) {
            return $source;
        }

        if (is_array($source)) {
            return ReportDefinition::fromArray($source);
        }

        throw new ReportRegistryException('Unsupported report definition source.');
    }
}
