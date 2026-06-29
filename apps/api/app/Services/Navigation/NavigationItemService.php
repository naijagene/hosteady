<?php

namespace App\Services\Navigation;

use App\Models\NavigationDefinition;
use App\Models\NavigationItem;
use App\Modules\Sdk\Navigation\Data\NavigationItem as NavigationItemDto;
use App\Modules\Sdk\Navigation\Enums\NavigationItemType;
use App\Modules\Sdk\Navigation\Enums\NavigationVisibility;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Modules\Sdk\Navigation\Exceptions\NavigationValidationException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class NavigationItemService
{
    public function __construct(
        private readonly NavigationAuditRecorder $auditRecorder,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /**
     * @param  NavigationItemDto|array<string, mixed>  $source
     */
    public function create(
        TenantContext $context,
        NavigationDefinition $definition,
        mixed $source,
        ?string $parentItemPublicId = null,
    ): NavigationItemDto {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            throw new NavigationValidationException('Navigation items table is not available.');
        }

        $item = $this->resolveItemSource($source);
        $this->assertNotDuplicate($context, $definition, $item);

        $parentItemId = NavigationMapper::resolveItemId($parentItemPublicId ?? $item->parentItemPublicId);

        if ($parentItemId !== null) {
            $this->assertValidParent($definition->id, $parentItemId);
        }

        $model = NavigationItem::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'navigation_definition_id' => $definition->id,
            'parent_item_id' => $parentItemId,
            'application_id' => NavigationMapper::resolveApplicationId($item->applicationPublicId),
            'module_key' => $item->moduleKey ?? $definition->module_key,
            'item_key' => $item->itemKey,
            'label' => $item->label !== '' ? $item->label : $item->itemKey,
            'item_type' => $item->itemType !== '' ? $item->itemType : NavigationItemType::Link->value,
            'route' => $item->route,
            'icon' => $item->icon,
            'badge_json' => $item->badge,
            'visibility' => $item->visibility !== '' ? $item->visibility : NavigationVisibility::Authenticated->value,
            'conditions_json' => $item->conditions,
            'permissions_json' => $item->permissions,
            'roles_json' => $item->roles,
            'sort_order' => $item->sortOrder,
            'metadata' => $item->metadata,
        ]);

        $created = NavigationMapper::toItem($model);
        $this->auditRecorder->recordItemCreated($created, $context);

        return $created;
    }

    public function update(TenantContext $context, NavigationItemDto $item): NavigationItemDto
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            throw new NavigationValidationException('Navigation items table is not available.');
        }

        $model = $this->resolveModel($context, $item->publicId);
        $before = NavigationMapper::toItem($model)->toArray();

        $parentItemId = NavigationMapper::resolveItemId($item->parentItemPublicId);

        if ($parentItemId !== null && $parentItemId === $model->id) {
            throw new NavigationValidationException('Navigation item cannot be its own parent.');
        }

        if ($parentItemId !== null) {
            $this->assertValidParent($model->navigation_definition_id, $parentItemId, $model->id);
        }

        $model->fill([
            'parent_item_id' => $parentItemId,
            'module_key' => $item->moduleKey,
            'item_key' => $item->itemKey,
            'label' => $item->label,
            'item_type' => $item->itemType,
            'route' => $item->route,
            'icon' => $item->icon,
            'badge_json' => $item->badge,
            'visibility' => $item->visibility,
            'conditions_json' => $item->conditions,
            'permissions_json' => $item->permissions,
            'roles_json' => $item->roles,
            'sort_order' => $item->sortOrder,
            'metadata' => $item->metadata,
            'application_id' => NavigationMapper::resolveApplicationId($item->applicationPublicId),
        ])->save();

        $updated = NavigationMapper::toItem($model->fresh());
        $this->auditRecorder->recordItemUpdated($updated, $before, $context);

        return $updated;
    }

    public function delete(TenantContext $context, string $itemPublicId): void
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            throw new NavigationValidationException('Navigation items table is not available.');
        }

        $model = $this->resolveModel($context, $itemPublicId);
        $before = NavigationMapper::toItem($model)->toArray();
        $model->delete();
        $this->auditRecorder->recordItemDeleted($itemPublicId, $before, $context);
    }

    /** @return list<NavigationItemDto> */
    public function listForDefinition(TenantContext $context, NavigationDefinition $definition): array
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            return [];
        }

        $query = NavigationItem::query()
            ->where('navigation_definition_id', $definition->id)
            ->orderBy('sort_order');

        NavigationMapper::applyOrganizationScope($query, $context->organization->id);
        NavigationMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->get()->map(fn (NavigationItem $model) => NavigationMapper::toItem($model))->all();
    }

    private function resolveModel(TenantContext $context, string $publicId): NavigationItem
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            throw new NavigationNotFoundException(sprintf('Navigation item [%s] was not found.', $publicId));
        }

        $query = NavigationItem::query()->where('public_id', $publicId);
        NavigationMapper::applyOrganizationScope($query, $context->organization->id);
        NavigationMapper::applyWorkspaceScope($query, $context->workspace?->id);

        $model = $query->first();

        if ($model === null) {
            throw new NavigationNotFoundException(sprintf('Navigation item [%s] was not found.', $publicId));
        }

        return $model;
    }

    private function assertNotDuplicate(TenantContext $context, NavigationDefinition $definition, NavigationItemDto $item): void
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            return;
        }

        $query = NavigationItem::query()
            ->where('navigation_definition_id', $definition->id)
            ->where('item_key', $item->itemKey);

        NavigationMapper::applyOrganizationScope($query, $context->organization->id);
        NavigationMapper::applyWorkspaceScope($query, $context->workspace?->id);

        if ($query->exists()) {
            throw new NavigationValidationException(sprintf(
                'Navigation item [%s] already exists for this definition.',
                $item->itemKey,
            ));
        }
    }

    private function assertValidParent(?string $definitionId, string $parentItemId, ?string $excludeItemId = null): void
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            throw new NavigationValidationException('Parent navigation item was not found for this definition.');
        }

        $parent = NavigationItem::query()
            ->where('id', $parentItemId)
            ->where('navigation_definition_id', $definitionId)
            ->first();

        if ($parent === null) {
            throw new NavigationValidationException('Parent navigation item was not found for this definition.');
        }

        if ($excludeItemId !== null && $this->wouldCreateCycle($excludeItemId, $parentItemId)) {
            throw new NavigationValidationException('Navigation item parent assignment would create a cycle.');
        }
    }

    private function wouldCreateCycle(string $itemId, string $parentItemId): bool
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_items')) {
            return false;
        }

        $visited = [];
        $current = $parentItemId;

        while ($current !== null && $current !== '') {
            if ($current === $itemId) {
                return true;
            }

            if (isset($visited[$current])) {
                return true;
            }

            $visited[$current] = true;
            $current = NavigationItem::query()->where('id', $current)->value('parent_item_id');
        }

        return false;
    }

    /**
     * @param  NavigationItemDto|array<string, mixed>  $source
     */
    private function resolveItemSource(mixed $source): NavigationItemDto
    {
        if ($source instanceof NavigationItemDto) {
            return $source;
        }

        if (is_array($source)) {
            return NavigationItemDto::fromArray($source);
        }

        throw new NavigationValidationException('Unsupported navigation item source.');
    }
}
