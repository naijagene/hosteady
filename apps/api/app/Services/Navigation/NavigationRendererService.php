<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Navigation\Contracts\NavigationRenderer;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Data\NavigationItem;
use App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
use App\Modules\Sdk\Navigation\Data\NavigationVersion as NavigationVersionDto;
use App\Modules\Sdk\Navigation\Exceptions\NavigationRenderException;
use App\Support\Tenant\TenantContext;

class NavigationRendererService implements NavigationRenderer
{
    public function __construct(
        private readonly NavigationRegistryService $registryService,
        private readonly NavigationItemService $itemService,
        private readonly NavigationTreeBuilderService $treeBuilder,
        private readonly NavigationVisibilityResolverService $visibilityResolver,
        private readonly NavigationPersonalizationService $personalizationService,
        private readonly NavigationPermissionBridge $permissionBridge,
        private readonly NavigationAuditRecorder $auditRecorder,
        private readonly NavigationUiBridge $uiBridge,
        private readonly NavigationRulesBridge $rulesBridge,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
        private readonly NavigationDraftService $draftService,
        private readonly NavigationVersionService $versionService,
    ) {
    }

    public function render(
        TenantContext $context,
        string $navigationKey,
        ?string $moduleKey = null,
        bool $previewDraft = false,
    ): NavigationRenderPayload {
        $permissions = $this->permissionBridge->renderPermissions($context);

        if (! $this->tableHealthSupport->coreTablesPresent()) {
            return $this->tableHealthSupport->emptyRenderPayload($context, $permissions, $moduleKey, $navigationKey);
        }

        $definition = $this->registryService->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $navigationKey,
        );

        if (! $this->visibilityResolver->evaluate($context, $definition->conditions)) {
            throw new NavigationRenderException(sprintf(
                'Navigation [%s] is not visible for the current context.',
                $navigationKey,
            ));
        }

        $definitionModel = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $navigationKey,
        );

        $version = $this->resolveVersion($context, $definition, $navigationKey, $moduleKey, $previewDraft);
        $items = $this->resolveVisibleItems($context, $definitionModel, $definition);
        $tree = $this->treeBuilder->build($items);
        $personalization = $this->personalizationService->get($context, $definition->publicId);

        $payload = new NavigationRenderPayload(
            definition: $definition->toArray(),
            version: $version?->toArray() ?? [],
            tree: $tree->nodes,
            items: array_map(fn (NavigationItem $item) => $item->toArray(), $items),
            permissions: $permissions,
            personalization: $personalization->personalization,
            runtimeContext: $this->buildRuntimeContext($context, $definition),
            warnings: $tree->warnings,
        );

        $this->auditRecorder->recordRendered($definition->publicId, $context);

        return $payload;
    }

    private function resolveVersion(
        TenantContext $context,
        NavigationDefinitionDto $definition,
        string $navigationKey,
        ?string $moduleKey,
        bool $previewDraft,
    ): ?NavigationVersionDto {
        if ($previewDraft) {
            return $this->draftService->loadDraft($context, $navigationKey, $moduleKey);
        }

        return $this->versionService->findPublishedVersion($context, $navigationKey, $moduleKey)
            ?? $this->draftService->loadDraft($context, $navigationKey, $moduleKey);
    }

    /** @return list<NavigationItem> */
    private function resolveVisibleItems(
        TenantContext $context,
        \App\Models\NavigationDefinition $definitionModel,
        NavigationDefinitionDto $definition,
    ): array {
        $items = $this->itemService->listForDefinition($context, $definitionModel);
        $visible = [];

        foreach ($items as $item) {
            if (! $this->visibilityResolver->isVisible($context, $item)) {
                continue;
            }

            $payload = $item->toArray();
            $pageRef = $this->uiBridge->resolvePageReferenceBestEffort($item->moduleKey, $item->route, $item->metadata);

            if ($pageRef !== null) {
                $payload['resolved_page'] = $pageRef;
            }

            $ruleRef = $this->rulesBridge->resolveReferenceBestEffort(
                $item->moduleKey,
                (string) ($item->metadata['rule_public_id'] ?? ''),
                $item->metadata,
            );

            if ($ruleRef !== null) {
                $payload['resolved_rule'] = $ruleRef;
            }

            $visible[] = NavigationItem::fromArray($payload);
        }

        return $visible;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeContext(TenantContext $context, NavigationDefinitionDto $definition): array
    {
        return [
            'organization_public_id' => $context->organizationPublicId,
            'workspace_public_id' => $context->workspacePublicId,
            'membership_public_id' => $context->membershipPublicId,
            'module_key' => $definition->moduleKey,
            'navigation_key' => $definition->navigationKey,
            'application_public_id' => $definition->applicationPublicId,
            'capabilities' => is_array($context->membership->metadata ?? null) ? $context->membership->metadata : [],
        ];
    }
}
