<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Models\DashboardWidget as DashboardWidgetModel;
use App\Modules\Sdk\Dashboard\Contracts\DashboardWidgetProvider;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException;

class DynamicDashboardWidgetService implements DashboardWidgetProvider
{
    public function __construct(
        private readonly DynamicDashboardAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return list<DashboardWidget>
     */
    public function listWidgets(DashboardDefinition $definition): array
    {
        $model = $this->resolveDefinitionModel($definition);

        return $model->widgets()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (DashboardWidgetModel $widget) => DynamicDashboardMapper::toWidget($widget))
            ->all();
    }

    public function createWidget(DashboardDefinition $definition, DashboardWidget $widget): DashboardWidget
    {
        $model = $this->resolveDefinitionModel($definition);

        $widgetModel = new DashboardWidgetModel;
        $widgetModel->dashboard_definition_id = $model->id;
        DynamicDashboardMapper::applyWidget($widgetModel, $widget);
        $widgetModel->save();

        $saved = DynamicDashboardMapper::toWidget($widgetModel);
        $this->auditRecorder->recordWidgetCreated($saved);

        return $saved;
    }

    public function updateWidget(DashboardWidget $widget): DashboardWidget
    {
        $model = DashboardWidgetModel::query()->where('public_id', $widget->publicId)->first();

        if ($model === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard widget [%s] was not found.', $widget->publicId));
        }

        DynamicDashboardMapper::applyWidget($model, $widget);
        $model->save();

        $saved = DynamicDashboardMapper::toWidget($model);
        $this->auditRecorder->recordWidgetUpdated($saved);

        return $saved;
    }

    public function deleteWidget(string $widgetPublicId): void
    {
        $model = DashboardWidgetModel::query()->where('public_id', $widgetPublicId)->first();

        if ($model === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard widget [%s] was not found.', $widgetPublicId));
        }

        $model->delete();
        $this->auditRecorder->recordWidgetDeleted($widgetPublicId);
    }

    private function resolveDefinitionModel(DashboardDefinition $definition): DashboardDefinitionModel
    {
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

        return $model;
    }
}
