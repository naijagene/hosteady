<?php

namespace App\Services\Ui;

use App\Models\UiPage;
use App\Modules\Sdk\Ui\Contracts\UiPageRegistry;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Modules\Sdk\Ui\Enums\UiPageStatus;
use App\Modules\Sdk\Ui\Enums\UiPageType;
use App\Modules\Sdk\Ui\Enums\UiVisibility;
use App\Modules\Sdk\Ui\Exceptions\UiPageNotFoundException;
use App\Modules\Sdk\Ui\Exceptions\UiRegistryException;
use Illuminate\Support\Str;

class UiPageRegistryService implements UiPageRegistry
{
    public function __construct(
        private readonly UiAuditRecorder $auditRecorder,
        private readonly UiSearchIndexer $searchIndexer,
        private readonly UiTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, UiPageDefinition $definition): UiPageDefinition
    {
        $definition = $this->resolveDefinitionSource($definition);
        $this->assertNotDuplicate($organizationId, $workspaceId, $definition);

        $model = UiPage::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_id' => $applicationId ?? UiMapper::resolveApplicationId($definition->applicationPublicId),
            'module_key' => $definition->moduleKey,
            'page_key' => $definition->pageKey,
            'name' => $definition->name !== '' ? $definition->name : $definition->pageKey,
            'description' => $definition->description,
            'page_type' => $definition->pageType !== '' ? $definition->pageType : UiPageType::Custom->value,
            'status' => $definition->status !== '' ? $definition->status : UiPageStatus::Draft->value,
            'visibility' => $definition->visibility !== '' ? $definition->visibility : UiVisibility::Workspace->value,
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
        ]);

        $created = UiMapper::toPageDefinition($model);
        $this->auditRecorder->recordPageRegistered($created);
        $this->searchIndexer->indexPageBestEffort($created, $organizationId, $workspaceId);

        return $created;
    }

    /** @return list<UiPageDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        if (! $this->tableHealthSupport->isTablePresent('ui_pages')) {
            return [];
        }

        $query = UiPage::query()
            ->orderBy('name')
            ->limit($limit);

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (UiPage $model) => UiMapper::toPageDefinition($model))->all();
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $pageKey): UiPageDefinition
    {
        $query = UiPage::query()
            ->where('module_key', $moduleKey)
            ->where('page_key', $pageKey);

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new UiPageNotFoundException(sprintf('UI page [%s.%s] was not found.', $moduleKey, $pageKey));
        }

        return UiMapper::toPageDefinition($model);
    }

    public function findByRoutePath(string $organizationId, ?string $workspaceId, string $routePath): UiPageDefinition
    {
        $query = UiPage::query()->where('route_path', $routePath);

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new UiPageNotFoundException(sprintf('UI page with route [%s] was not found.', $routePath));
        }

        return UiMapper::toPageDefinition($model);
    }

    /**
     * @param  UiPageDefinition|array<string, mixed>  $source
     */
    public function registerFromSource(string $organizationId, ?string $workspaceId, ?string $applicationId, mixed $source): UiPageDefinition
    {
        return $this->register($organizationId, $workspaceId, $applicationId, $this->resolveDefinitionSource($source));
    }

    private function assertNotDuplicate(string $organizationId, ?string $workspaceId, UiPageDefinition $definition): void
    {
        $query = UiPage::query()
            ->where('module_key', $definition->moduleKey)
            ->where('page_key', $definition->pageKey);

        UiMapper::applyOrganizationScope($query, $organizationId);
        UiMapper::applyWorkspaceScope($query, $workspaceId);

        if ($query->exists()) {
            throw new UiRegistryException(sprintf(
                'UI page [%s.%s] is already registered.',
                $definition->moduleKey ?? '',
                $definition->pageKey,
            ));
        }
    }

    /**
     * @param  UiPageDefinition|array<string, mixed>  $source
     */
    private function resolveDefinitionSource(mixed $source): UiPageDefinition
    {
        if ($source instanceof UiPageDefinition) {
            return $source;
        }

        if (is_array($source)) {
            return UiPageDefinition::fromArray($source);
        }

        throw new UiRegistryException('Unsupported UI page definition source.');
    }
}
