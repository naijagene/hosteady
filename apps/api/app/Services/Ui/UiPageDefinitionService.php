<?php

namespace App\Services\Ui;

use App\Models\UiPage;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Exceptions\UiPageNotFoundException;
use App\Support\Tenant\TenantContext;

class UiPageDefinitionService
{
    public function __construct(
        private readonly UiPageRegistryService $registryService,
        private readonly UiAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @param  UiPageDefinition|array<string, mixed>  $source
     */
    public function create(TenantContext $context, mixed $source): UiPageDefinition
    {
        return $this->registryService->registerFromSource(
            $context->organization->id,
            $context->workspace?->id,
            null,
            $source,
        );
    }

    public function update(TenantContext $context, UiPageDefinition $definition): UiPageDefinition
    {
        $model = $this->resolveModel($context, (string) $definition->moduleKey, $definition->pageKey);
        $before = UiMapper::toPageDefinition($model)->toArray();

        $model->fill([
            'module_key' => $definition->moduleKey,
            'page_key' => $definition->pageKey,
            'name' => $definition->name,
            'description' => $definition->description,
            'page_type' => $definition->pageType,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'route_path' => $definition->routePath,
            'icon' => $definition->icon,
            'layout_json' => $definition->layout,
            'regions_json' => $definition->regions,
            'components_json' => $definition->components,
            'actions_json' => $definition->actions,
            'conditions_json' => $definition->conditions,
            'breakpoints_json' => $definition->breakpoints,
            'theme_json' => $definition->theme,
            'metadata' => $definition->metadata,
            'application_id' => UiMapper::resolveApplicationId($definition->applicationPublicId),
        ])->save();

        $updated = UiMapper::toPageDefinition($model->fresh());
        $this->auditRecorder->recordPageUpdated($updated, $before, $context);

        return $updated;
    }

    public function delete(TenantContext $context, string $moduleKey, string $pageKey): void
    {
        $model = $this->resolveModel($context, $moduleKey, $pageKey);
        $before = UiMapper::toPageDefinition($model)->toArray();
        $model->delete();
        $this->auditRecorder->recordPageDeleted($model->public_id, $before, $context);
    }

    public function find(TenantContext $context, string $moduleKey, string $pageKey): UiPageDefinition
    {
        return $this->registryService->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $pageKey,
        );
    }

    public function findByPublicId(TenantContext $context, string $publicId): UiPageDefinition
    {
        $query = UiPage::query()->where('public_id', $publicId);
        UiMapper::applyOrganizationScope($query, $context->organization->id);
        UiMapper::applyWorkspaceScope($query, $context->workspace?->id);

        $model = $query->first();

        if ($model === null) {
            throw new UiPageNotFoundException(sprintf('UI page [%s] was not found.', $publicId));
        }

        return UiMapper::toPageDefinition($model);
    }

    /** @return list<UiPageDefinition> */
    public function list(TenantContext $context, int $limit = 50): array
    {
        return $this->registryService->list(
            $context->organization->id,
            $context->workspace?->id,
            $limit,
        );
    }

    private function resolveModel(TenantContext $context, string $moduleKey, string $pageKey): UiPage
    {
        $query = UiPage::query()
            ->where('module_key', $moduleKey)
            ->where('page_key', $pageKey);

        UiMapper::applyOrganizationScope($query, $context->organization->id);
        UiMapper::applyWorkspaceScope($query, $context->workspace?->id);

        $model = $query->first();

        if ($model === null) {
            throw new UiPageNotFoundException(sprintf('UI page [%s.%s] was not found.', $moduleKey, $pageKey));
        }

        return $model;
    }
}
