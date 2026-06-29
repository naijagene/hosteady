<?php

namespace App\Services\Navigation;

use App\Models\NavigationDefinition;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Modules\Sdk\Navigation\Exceptions\NavigationValidationException;
use App\Support\Tenant\TenantContext;

class NavigationDefinitionService
{
    public function __construct(
        private readonly NavigationRegistryService $registryService,
        private readonly NavigationAuditRecorder $auditRecorder,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /**
     * @param  NavigationDefinitionDto|array<string, mixed>  $source
     */
    public function create(TenantContext $context, mixed $source): NavigationDefinitionDto
    {
        $this->assertDefinitionsTablePresent();

        return $this->registryService->registerFromSource(
            $context->organization->id,
            $context->workspace?->id,
            null,
            $source,
        );
    }

    public function update(TenantContext $context, NavigationDefinitionDto $definition): NavigationDefinitionDto
    {
        $this->assertDefinitionsTablePresent();

        $model = $this->resolveModel($context, (string) $definition->moduleKey, $definition->navigationKey);
        $before = NavigationMapper::toDefinition($model)->toArray();

        $model->fill([
            'module_key' => $definition->moduleKey,
            'navigation_key' => $definition->navigationKey,
            'name' => $definition->name,
            'description' => $definition->description,
            'type' => $definition->type,
            'status' => $definition->status,
            'visibility' => $definition->visibility,
            'scope' => $definition->scope,
            'structure_json' => $definition->structure,
            'conditions_json' => $definition->conditions,
            'metadata' => $definition->metadata,
            'application_id' => NavigationMapper::resolveApplicationId($definition->applicationPublicId),
        ])->save();

        $updated = NavigationMapper::toDefinition($model->fresh());
        $this->auditRecorder->recordDefinitionUpdated($updated, $before, $context);

        return $updated;
    }

    public function delete(TenantContext $context, string $moduleKey, string $navigationKey): void
    {
        $this->assertDefinitionsTablePresent();

        $model = $this->resolveModel($context, $moduleKey, $navigationKey);
        $before = NavigationMapper::toDefinition($model)->toArray();
        $model->delete();
        $this->auditRecorder->recordDefinitionDeleted($model->public_id, $before, $context);
    }

    public function find(TenantContext $context, string $moduleKey, string $navigationKey): NavigationDefinitionDto
    {
        return $this->registryService->findByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $navigationKey,
        );
    }

    public function findByPublicId(TenantContext $context, string $publicId): NavigationDefinitionDto
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_definitions')) {
            throw new NavigationNotFoundException(sprintf('Navigation definition [%s] was not found.', $publicId));
        }

        return NavigationMapper::toDefinition(
            $this->registryService->resolveModelByPublicId(
                $context->organization->id,
                $context->workspace?->id,
                $publicId,
            ),
        );
    }

    /** @return list<NavigationDefinitionDto> */
    public function list(TenantContext $context, int $limit = 50): array
    {
        return $this->registryService->list(
            $context->organization->id,
            $context->workspace?->id,
            $limit,
        );
    }

    private function resolveModel(TenantContext $context, string $moduleKey, string $navigationKey): NavigationDefinition
    {
        return $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $navigationKey,
        );
    }

    private function assertDefinitionsTablePresent(): void
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_definitions')) {
            throw new NavigationValidationException('Navigation definitions table is not available.');
        }
    }
}
