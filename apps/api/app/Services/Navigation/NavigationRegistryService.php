<?php

namespace App\Services\Navigation;

use App\Models\NavigationDefinition;
use App\Modules\Sdk\Navigation\Contracts\NavigationRegistry;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Enums\NavigationDefinitionStatus;
use App\Modules\Sdk\Navigation\Enums\NavigationScope;
use App\Modules\Sdk\Navigation\Enums\NavigationType;
use App\Modules\Sdk\Navigation\Enums\NavigationVisibility;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Modules\Sdk\Navigation\Exceptions\NavigationRegistryException;
use App\Modules\Sdk\Navigation\Exceptions\NavigationValidationException;
use Illuminate\Support\Str;

class NavigationRegistryService implements NavigationRegistry
{
    public function __construct(
        private readonly NavigationAuditRecorder $auditRecorder,
        private readonly NavigationSearchIndexer $searchIndexer,
        private readonly NavigationTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function register(
        string $organizationId,
        ?string $workspaceId,
        ?string $applicationId,
        NavigationDefinitionDto $definition,
    ): NavigationDefinitionDto {
        $this->assertDefinitionsTablePresent();
        $definition = $this->resolveDefinitionSource($definition);
        $this->assertNotDuplicate($organizationId, $workspaceId, $definition);

        $model = NavigationDefinition::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_id' => $applicationId ?? NavigationMapper::resolveApplicationId($definition->applicationPublicId),
            'module_key' => $definition->moduleKey,
            'navigation_key' => $definition->navigationKey,
            'name' => $definition->name !== '' ? $definition->name : $definition->navigationKey,
            'description' => $definition->description,
            'type' => $definition->type !== '' ? $definition->type : NavigationType::Primary->value,
            'status' => $definition->status !== '' ? $definition->status : NavigationDefinitionStatus::Draft->value,
            'visibility' => $definition->visibility !== '' ? $definition->visibility : NavigationVisibility::Authenticated->value,
            'scope' => $definition->scope !== '' ? $definition->scope : NavigationScope::Workspace->value,
            'structure_json' => $definition->structure,
            'conditions_json' => $definition->conditions,
            'metadata' => $definition->metadata,
        ]);

        $created = NavigationMapper::toDefinition($model);
        $this->auditRecorder->recordDefinitionRegistered($created);
        $this->searchIndexer->indexDefinitionBestEffort($created, $organizationId, $workspaceId);

        return $created;
    }

    /** @return list<NavigationDefinitionDto> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_definitions')) {
            return [];
        }

        $query = NavigationDefinition::query()
            ->orderBy('name')
            ->limit($limit);

        NavigationMapper::applyOrganizationScope($query, $organizationId);
        NavigationMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (NavigationDefinition $model) => NavigationMapper::toDefinition($model))->all();
    }

    public function findByKey(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $navigationKey,
    ): NavigationDefinitionDto {
        $this->assertDefinitionsTablePresent();

        $model = $this->resolveModelByKey($organizationId, $workspaceId, $moduleKey, $navigationKey);

        return NavigationMapper::toDefinition($model);
    }

    /**
     * @param  NavigationDefinitionDto|array<string, mixed>  $source
     */
    public function registerFromSource(
        string $organizationId,
        ?string $workspaceId,
        ?string $applicationId,
        mixed $source,
    ): NavigationDefinitionDto {
        return $this->register($organizationId, $workspaceId, $applicationId, $this->resolveDefinitionSource($source));
    }

    public function resolveModelByKey(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $navigationKey,
    ): NavigationDefinition {
        $this->assertDefinitionsTablePresent();

        $query = NavigationDefinition::query()
            ->where('navigation_key', $navigationKey);

        if ($moduleKey !== '') {
            $query->where('module_key', $moduleKey);
        }

        NavigationMapper::applyOrganizationScope($query, $organizationId);
        NavigationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new NavigationNotFoundException(sprintf(
                'Navigation [%s.%s] was not found.',
                $moduleKey,
                $navigationKey,
            ));
        }

        return $model;
    }

    public function resolveModelByPublicId(
        string $organizationId,
        ?string $workspaceId,
        string $publicId,
    ): NavigationDefinition {
        $this->assertDefinitionsTablePresent();

        $query = NavigationDefinition::query()->where('public_id', $publicId);
        NavigationMapper::applyOrganizationScope($query, $organizationId);
        NavigationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new NavigationNotFoundException(sprintf('Navigation definition [%s] was not found.', $publicId));
        }

        return $model;
    }

    private function assertNotDuplicate(string $organizationId, ?string $workspaceId, NavigationDefinitionDto $definition): void
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_definitions')) {
            return;
        }

        $query = NavigationDefinition::query()
            ->where('navigation_key', $definition->navigationKey);

        if ($definition->moduleKey !== null && $definition->moduleKey !== '') {
            $query->where('module_key', $definition->moduleKey);
        }

        NavigationMapper::applyOrganizationScope($query, $organizationId);
        NavigationMapper::applyWorkspaceScope($query, $workspaceId);

        if ($query->exists()) {
            throw new NavigationRegistryException(sprintf(
                'Navigation [%s.%s] is already registered.',
                $definition->moduleKey ?? '',
                $definition->navigationKey,
            ));
        }
    }

    /**
     * @param  NavigationDefinitionDto|array<string, mixed>  $source
     */
    private function resolveDefinitionSource(mixed $source): NavigationDefinitionDto
    {
        if ($source instanceof NavigationDefinitionDto) {
            return $source;
        }

        if (is_array($source)) {
            return NavigationDefinitionDto::fromArray($source);
        }

        throw new NavigationRegistryException('Unsupported navigation definition source.');
    }

    private function assertDefinitionsTablePresent(): void
    {
        if (! $this->tableHealthSupport->isTablePresent('navigation_definitions')) {
            throw new NavigationValidationException('Navigation definitions table is not available.');
        }
    }
}
