<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Models\DashboardView as DashboardViewModel;
use App\Modules\Sdk\Dashboard\Data\DashboardFilter;
use App\Modules\Sdk\Dashboard\Data\DashboardLayout;
use App\Modules\Sdk\Dashboard\Data\DashboardView;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException;
use Illuminate\Support\Str;

class DynamicDashboardViewService
{
    public function __construct(
        private readonly DynamicDashboardAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return list<DashboardView>
     */
    public function listViews(string $moduleKey, string $dashboardKey, string $organizationId, ?string $workspaceId = null): array
    {
        $definition = DashboardDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('dashboard_key', $dashboardKey)
            ->first();

        if ($definition === null) {
            return [];
        }

        $query = DashboardViewModel::query()
            ->where('dashboard_definition_id', $definition->id)
            ->where('organization_id', $organizationId)
            ->orderBy('name');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        return $query->get()
            ->map(fn (DashboardViewModel $model) => DynamicDashboardMapper::toView($model))
            ->all();
    }

    public function saveView(string $moduleKey, string $dashboardKey, DashboardView $view): DashboardView
    {
        $definition = DashboardDefinitionModel::query()
            ->where('module_key', $moduleKey)
            ->where('dashboard_key', $dashboardKey)
            ->first();

        if ($definition === null) {
            throw new DashboardNotFoundException(sprintf(
                'Dashboard definition [%s.%s] was not found.',
                $moduleKey,
                $dashboardKey,
            ));
        }

        if ($view->isDefault) {
            DashboardViewModel::query()
                ->where('dashboard_definition_id', $definition->id)
                ->where('organization_id', $view->organizationId)
                ->when($view->workspaceId !== null, fn ($q) => $q->where('workspace_id', $view->workspaceId))
                ->when($view->workspaceId === null, fn ($q) => $q->whereNull('workspace_id'))
                ->update(['is_default' => false]);
        }

        $model = $view->publicId !== null
            ? DashboardViewModel::query()->where('public_id', $view->publicId)->first()
            : null;

        if ($model === null) {
            $model = new DashboardViewModel([
                'id' => (string) Str::uuid7(),
            ]);
        }

        $model->fill([
            'organization_id' => $view->organizationId,
            'workspace_id' => $view->workspaceId,
            'dashboard_definition_id' => $definition->id,
            'name' => $view->name,
            'layout_json' => $view->layout?->toArray(),
            'filters_json' => array_map(fn (DashboardFilter $f) => $f->toArray(), $view->filters),
            'is_default' => $view->isDefault,
        ]);
        $model->save();

        $saved = DynamicDashboardMapper::toView($model);
        $this->auditRecorder->recordViewCreated($saved);

        return $saved;
    }

    public function setDefaultView(string $viewPublicId, string $organizationId, ?string $workspaceId = null): DashboardView
    {
        $query = DashboardViewModel::query()
            ->where('public_id', $viewPublicId)
            ->where('organization_id', $organizationId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        $model = $query->first();

        if ($model === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard view [%s] was not found.', $viewPublicId));
        }

        DashboardViewModel::query()
            ->where('dashboard_definition_id', $model->dashboard_definition_id)
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->when($workspaceId === null, fn ($q) => $q->whereNull('workspace_id'))
            ->update(['is_default' => false]);

        $model->is_default = true;
        $model->save();

        $saved = DynamicDashboardMapper::toView($model);
        $this->auditRecorder->recordViewUpdated($saved);

        return $saved;
    }

    public function deleteView(string $viewPublicId, string $organizationId, ?string $workspaceId = null): void
    {
        $query = DashboardViewModel::query()
            ->where('public_id', $viewPublicId)
            ->where('organization_id', $organizationId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        } else {
            $query->whereNull('workspace_id');
        }

        $model = $query->first();

        if ($model === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard view [%s] was not found.', $viewPublicId));
        }

        $model->delete();
        $this->auditRecorder->recordViewDeleted($viewPublicId);
    }
}
