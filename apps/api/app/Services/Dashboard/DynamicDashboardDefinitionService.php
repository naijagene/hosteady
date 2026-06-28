<?php

namespace App\Services\Dashboard;

use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Modules\Sdk\Dashboard\Data\DashboardDefinition;
use App\Modules\Sdk\Dashboard\Exceptions\DashboardNotFoundException;
use App\Support\Tenant\TenantContext;

class DynamicDashboardDefinitionService
{
    public function __construct(
        private readonly DynamicDashboardRegistryService $registryService,
    ) {
    }

    public function create(DashboardDefinition $definition): DashboardDefinition
    {
        return $this->registryService->register($definition);
    }

    public function update(DashboardDefinition $definition): DashboardDefinition
    {
        return $this->registryService->update($definition);
    }

    public function delete(string $moduleKey, string $dashboardKey): void
    {
        $model = $this->resolveModel($moduleKey, $dashboardKey);
        $model->delete();
    }

    public function find(string $moduleKey, string $dashboardKey): DashboardDefinition
    {
        $definition = $this->registryService->find($moduleKey, $dashboardKey);

        if ($definition === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard [%s.%s] was not found.', $moduleKey, $dashboardKey));
        }

        return $definition;
    }

    public function findByPublicId(string $publicId): DashboardDefinition
    {
        $definition = $this->registryService->findByPublicId($publicId);

        if ($definition === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard [%s] was not found.', $publicId));
        }

        return $definition;
    }

    /**
     * @return list<DashboardDefinition>
     */
    public function list(?string $moduleKey = null): array
    {
        return $this->registryService->list($moduleKey);
    }

    /**
     * @return list<DashboardDefinition>
     */
    public function listByEntity(string $moduleKey, string $entityKey): array
    {
        return $this->registryService->findByEntity($moduleKey, $entityKey);
    }

    /**
     * @param  list<array<string, mixed>>  $dashboards
     * @return list<DashboardDefinition>
     */
    public function registerFromManifest(array $dashboards, string $moduleKey): array
    {
        return $this->registryService->registerFromManifestDashboards($dashboards, $moduleKey);
    }

    private function resolveModel(string $moduleKey, string $dashboardKey): DashboardDefinitionModel
    {
        $definition = $this->registryService->find($moduleKey, $dashboardKey);

        if ($definition === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard [%s.%s] was not found.', $moduleKey, $dashboardKey));
        }

        $query = DashboardDefinitionModel::query()->where('public_id', $definition->publicId);

        if (app()->bound(TenantContext::class)) {
            $context = app(TenantContext::class);
            $query->where('organization_id', $context->organization->id);

            if ($context->workspace !== null) {
                $query->where('workspace_id', $context->workspace->id);
            } else {
                $query->whereNull('workspace_id');
            }
        }

        $model = $query->first();

        if ($model === null) {
            throw new DashboardNotFoundException(sprintf('Dashboard [%s.%s] was not found.', $moduleKey, $dashboardKey));
        }

        return $model;
    }
}
