<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Modules\Sdk\Dashboard\Contracts\DashboardRegistry;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardRegistryException;
use Illuminate\Support\Facades\DB;

class DynamicDashboardRegistryService implements DashboardRegistry
{
    public function __construct(
        private readonly DynamicDashboardValidationService $validator,
        private readonly DynamicDashboardAuditRecorder $auditRecorder,
        private readonly DynamicDashboardSearchIndexer $searchIndexer,
        private readonly DynamicDashboardWorkflowBridge $workflowBridge,
    ) {
    }

    public function register(mixed $source): DashboardDefinition
    {
        $definition = $this->resolveDefinitionSource($source);
        $this->validator->assertValid($definition);

        if (DashboardDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('dashboard_key', $definition->dashboardKey)
            ->whereNull('organization_id')
            ->whereNull('workspace_id')
            ->exists()) {
            throw new DashboardRegistryException(sprintf(
                'Dashboard definition [%s.%s] is already registered.',
                $definition->moduleKey,
                $definition->dashboardKey,
            ));
        }

        return DB::transaction(function () use ($definition) {
            $model = new DashboardDefinitionModel;
            DynamicDashboardMapper::applyDefinition($model, $definition);
            $model->save();

            foreach ($definition->widgets as $widget) {
                $widgetModel = new \App\Models\DashboardWidget;
                $widgetModel->dashboard_definition_id = $model->id;
                DynamicDashboardMapper::applyWidget($widgetModel, $widget);
                $widgetModel->save();
            }

            $this->auditRecorder->recordDefinitionRegistered($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);
            $this->workflowBridge->triggerDefinitionRegisteredBestEffort($model);

            return DynamicDashboardMapper::toDefinition($model->fresh(['widgets']));
        });
    }

    public function update(DashboardDefinition $definition): DashboardDefinition
    {
        $this->validator->assertValid($definition);

        $model = DashboardDefinitionModel::query()
            ->where('module_key', $definition->moduleKey)
            ->where('dashboard_key', $definition->dashboardKey)
            ->first();

        if ($model === null) {
            throw new DashboardNotFoundException(sprintf(
                'Dashboard definition [%s.%s] was not found.',
                $definition->moduleKey,
                $definition->dashboardKey,
            ));
        }

        return DB::transaction(function () use ($model, $definition) {
            DynamicDashboardMapper::applyDefinition($model, $definition);
            $model->save();

            $this->auditRecorder->recordDefinitionUpdated($model);
            $this->searchIndexer->indexDefinitionBestEffort($model);
            $this->workflowBridge->triggerDefinitionUpdatedBestEffort($model);

            return DynamicDashboardMapper::toDefinition($model->fresh(['widgets']));
        });
    }

    public function find(string $moduleKey, string $dashboardKey): ?DashboardDefinition
    {
        $model = DashboardDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('dashboard_key', $dashboardKey)
            ->first();

        return $model === null ? null : DynamicDashboardMapper::toDefinition($model->load('widgets'));
    }

    public function findByPublicId(string $publicId): ?DashboardDefinition
    {
        $model = DashboardDefinitionModel::query()->where('public_id', $publicId)->first();

        return $model === null ? null : DynamicDashboardMapper::toDefinition($model->load('widgets'));
    }

    /**
     * @return list<DashboardDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        $query = DashboardDefinitionModel::query()->orderBy('module_key')->orderBy('dashboard_key');

        if ($moduleKey !== null) {
            $query->where('module_key', $moduleKey);
        }

        return $query->get()
            ->map(fn (DashboardDefinitionModel $model) => DynamicDashboardMapper::toDefinition($model->load('widgets')))
            ->all();
    }

    /**
     * @return list<DashboardDefinition>
     */
    public function findByEntity(string $moduleKey, string $entityKey): array
    {
        return DashboardDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->orderBy('dashboard_key')
            ->get()
            ->map(fn (DashboardDefinitionModel $model) => DynamicDashboardMapper::toDefinition($model->load('widgets')))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $dashboards
     * @return list<DashboardDefinition>
     */
    public function registerFromManifestDashboards(array $dashboards, string $moduleKey): array
    {
        $registered = [];

        foreach ($dashboards as $dashboard) {
            if (! is_array($dashboard)) {
                continue;
            }

            $payload = array_merge($dashboard, ['module_key' => $moduleKey]);
            $dashboardKey = (string) ($payload['dashboard_key'] ?? $payload['key'] ?? '');

            if ($dashboardKey === '') {
                continue;
            }

            if ($this->find($moduleKey, $dashboardKey) !== null) {
                continue;
            }

            $registered[] = $this->register(DashboardDefinition::fromArray($payload));
        }

        return $registered;
    }

    public function findModel(string $moduleKey, string $dashboardKey): ?DashboardDefinitionModel
    {
        return DashboardDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('dashboard_key', $dashboardKey)
            ->first();
    }

    private function resolveDefinitionSource(mixed $source): DashboardDefinition
    {
        if ($source instanceof DashboardDefinition) {
            return $source;
        }

        if (is_array($source)) {
            return DashboardDefinition::fromArray($source);
        }

        throw new DashboardRegistryException('Unsupported dashboard definition source.');
    }
}
