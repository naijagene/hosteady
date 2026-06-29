<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Navigation\Data\NavigationDefinition;
use App\Modules\Sdk\Navigation\Data\NavigationHealthReport;
use App\Modules\Sdk\Navigation\Data\NavigationItem;
use App\Modules\Sdk\Navigation\Data\NavigationPersonalization;
use App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
use App\Modules\Sdk\Navigation\Data\NavigationStatistics;
use App\Modules\Sdk\Navigation\Data\NavigationTree;
use App\Modules\Sdk\Navigation\Data\NavigationVersion;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Modules\Sdk\Navigation\Exceptions\NavigationRenderException;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NavigationDevelopmentService
{
    public function __construct(
        private readonly NavigationDefinitionService $definitionService,
        private readonly NavigationItemService $itemService,
        private readonly NavigationRendererService $rendererService,
        private readonly NavigationDraftService $draftService,
        private readonly NavigationVersionService $versionService,
        private readonly NavigationPublisherService $publisherService,
        private readonly NavigationPersonalizationService $personalizationService,
        private readonly NavigationDefaultGeneratorService $defaultGeneratorService,
        private readonly NavigationHealthService $healthService,
        private readonly NavigationStatisticsService $statisticsService,
        private readonly NavigationPermissionBridge $permissionBridge,
        private readonly NavigationRegistryService $registryService,
        private readonly NavigationTreeBuilderService $treeBuilderService,
        private readonly NavigationApplicationRuntimeBridge $applicationRuntimeBridge,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<NavigationDefinition> */
    public function listDefinitions(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->list($context);
    }

    /**
     * @param  NavigationDefinition|array<string, mixed>  $definition
     */
    public function registerDefinition(TenantContext $context, mixed $definition): NavigationDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->definitionService->create($context, $definition);
    }

    public function findDefinitionByPublicId(TenantContext $context, string $publicId): NavigationDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->findByPublicId($context, $publicId);
    }

    public function findDefinition(TenantContext $context, string $moduleKey, string $navigationKey): NavigationDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->definitionService->find($context, $moduleKey, $navigationKey);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDefinitionByPublicId(TenantContext $context, string $publicId, array $data): NavigationDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $existing = $this->definitionService->findByPublicId($context, $publicId);

        return $this->definitionService->update($context, NavigationDefinition::fromArray(array_merge($existing->toArray(), $data)));
    }

    /** @return list<NavigationItem> */
    public function listItems(TenantContext $context, string $definitionPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $definitionPublicId,
        );

        return $this->itemService->listForDefinition($context, $definition);
    }

    /**
     * @param  NavigationItem|array<string, mixed>  $item
     */
    public function createItemForDefinition(TenantContext $context, string $definitionPublicId, mixed $item): NavigationItem
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $definition = $this->registryService->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $definitionPublicId,
        );

        return $this->itemService->create($context, $definition, $item);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(TenantContext $context, string $itemPublicId, array $data): NavigationItem
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $query = \App\Models\NavigationItem::query()->where('public_id', $itemPublicId);
        NavigationMapper::applyOrganizationScope($query, $context->organization->id);
        NavigationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        $model = $query->firstOrFail();
        $existing = NavigationMapper::toItem($model);

        return $this->itemService->update($context, NavigationItem::fromArray(array_merge($existing->toArray(), $data)));
    }

    public function deleteItem(TenantContext $context, string $itemPublicId): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->itemService->delete($context, $itemPublicId);
    }

    public function buildTree(TenantContext $context, string $definitionPublicId, bool $previewDraft = false): NavigationTree
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->treeBuilderService->emptyTree();
        }

        $definition = $this->definitionService->findByPublicId($context, $definitionPublicId);
        $payload = $this->rendererService->render(
            $context,
            $definition->navigationKey,
            $definition->moduleKey,
            $previewDraft,
        );

        return new NavigationTree(
            nodes: $payload->tree,
            warnings: $payload->warnings,
        );
    }

    public function renderDefinition(TenantContext $context, string $definitionPublicId, bool $previewDraft = false): NavigationRenderPayload
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->definitionService->findByPublicId($context, $definitionPublicId);

        return $this->rendererService->render(
            $context,
            $definition->navigationKey,
            $definition->moduleKey,
            $previewDraft,
        );
    }

    public function createVersion(TenantContext $context, string $definitionPublicId, array $structure = []): NavigationVersion
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $definition = $this->definitionService->findByPublicId($context, $definitionPublicId);

        return $this->draftService->saveDraft(
            $context,
            $definition->navigationKey,
            $structure,
            $definition->moduleKey,
        );
    }

    public function publishDefinition(
        TenantContext $context,
        string $definitionPublicId,
        ?string $versionPublicId = null,
    ): NavigationDefinition {
        $this->requireCapability($context);
        $this->assertPublish($context);

        $definition = $this->definitionService->findByPublicId($context, $definitionPublicId);

        return $this->publisherService->publish(
            $context,
            $definition->navigationKey,
            $versionPublicId,
            $definition->moduleKey,
        );
    }

    /** @return list<NavigationVersion> */
    public function listVersionsForDefinition(TenantContext $context, string $definitionPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->definitionService->findByPublicId($context, $definitionPublicId);

        return $this->versionService->listVersions($context, $definition->navigationKey, $definition->moduleKey);
    }

    public function renderNavigation(
        TenantContext $context,
        string $navigationKey,
        ?string $moduleKey = null,
        bool $previewDraft = false,
    ): NavigationRenderPayload {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->rendererService->render($context, $navigationKey, $moduleKey, $previewDraft);
    }

    /**
     * @param  NavigationItem|array<string, mixed>  $item
     */
    public function createItem(
        TenantContext $context,
        string $moduleKey,
        string $navigationKey,
        mixed $item,
        ?string $parentItemPublicId = null,
    ): NavigationItem {
        $this->requireCapability($context);
        $this->assertManage($context);

        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $navigationKey,
        );

        return $this->itemService->create($context, $definition, $item, $parentItemPublicId);
    }

    public function saveDraft(TenantContext $context, string $navigationKey, array $structure, ?string $moduleKey = null): NavigationVersion
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->draftService->saveDraft($context, $navigationKey, $structure, $moduleKey);
    }

    public function publishNavigation(
        TenantContext $context,
        string $navigationKey,
        ?string $versionPublicId = null,
        ?string $moduleKey = null,
    ): NavigationDefinition {
        $this->requireCapability($context);
        $this->assertPublish($context);

        return $this->publisherService->publish($context, $navigationKey, $versionPublicId, $moduleKey);
    }

    /** @return list<NavigationVersion> */
    public function listVersions(TenantContext $context, string $navigationKey, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->versionService->listVersions($context, $navigationKey, $moduleKey);
    }

    public function generateDefault(TenantContext $context, string $navigationKey, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->defaultGeneratorService->generateBestEffort($context, $navigationKey, $moduleKey);
    }

    public function health(TenantContext $context): NavigationHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): NavigationStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    public function updatePersonalization(
        TenantContext $context,
        string $navigationDefinitionPublicId,
        array $personalization,
    ): NavigationPersonalization {
        $this->requireCapability($context);
        $this->assertPersonalize($context);

        return $this->personalizationService->update($context, $navigationDefinitionPublicId, $personalization);
    }

    /**
     * @return array<string, mixed>
     */
    public function composeRuntime(
        TenantContext $context,
        ?string $navigationKey = 'main',
        ?string $moduleKey = null,
    ): array {
        $this->requireCapability($context);
        $this->assertRead($context);

        $permissions = $this->permissionBridge->renderPermissions($context);
        $navigationKey ??= 'main';

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return [
                'menus' => [],
                'tree' => [],
                'warnings' => $this->tableHealthSupport->warningsForCoreTables(),
                'runtime_context' => $this->tableHealthSupport->missingTablesRuntimeContext($context, $permissions),
                'permissions' => $permissions,
                'source' => 'navigation_designer',
            ];
        }

        try {
            $payload = $this->rendererService->render($context, $navigationKey, $moduleKey, false);
            $menu = $this->applicationRuntimeBridge->convertPayloadToMenu($payload, $navigationKey);

            return [
                'menus' => $menu !== null ? [$menu->toArray()] : [],
                'tree' => $payload->tree,
                'warnings' => $payload->warnings,
                'runtime_context' => $payload->runtimeContext,
                'permissions' => $permissions,
                'source' => 'navigation_designer',
            ];
        } catch (NavigationNotFoundException|NavigationRenderException) {
            return [
                'menus' => [],
                'tree' => [],
                'warnings' => [],
                'runtime_context' => [
                    'navigation_key' => $navigationKey,
                    'module_key' => $moduleKey,
                    'organization_public_id' => $context->organizationPublicId,
                    'workspace_public_id' => $context->workspacePublicId,
                ],
                'permissions' => $permissions,
                'source' => 'navigation_designer',
            ];
        }
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.navigation_designer.enabled', true)) {
            throw new HttpException(503, 'Navigation designer is disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'navigation_designer');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionBridge->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read navigation.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionBridge->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage navigation.');
        }
    }

    private function assertPublish(TenantContext $context): void
    {
        if (! $this->permissionBridge->canPublish($context)) {
            throw new HttpException(403, 'You do not have permission to publish navigation.');
        }
    }

    private function assertPersonalize(TenantContext $context): void
    {
        if (! $this->permissionBridge->canPersonalize($context)) {
            throw new HttpException(403, 'You do not have permission to personalize navigation.');
        }
    }
}
