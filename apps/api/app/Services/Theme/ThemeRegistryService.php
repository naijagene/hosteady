<?php

namespace App\Services\Theme;

use App\Models\ThemeDefinition;
use App\Modules\Sdk\Theme\Contracts\ThemeRegistry;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus;
use App\Modules\Sdk\Theme\Enums\ThemeInheritanceMode;
use App\Modules\Sdk\Theme\Enums\ThemeScope;
use App\Modules\Sdk\Theme\Exceptions\ThemeNotFoundException;
use App\Modules\Sdk\Theme\Exceptions\ThemeRegistryException;
use App\Modules\Sdk\Theme\Exceptions\ThemeValidationException;
use Illuminate\Support\Str;

class ThemeRegistryService implements ThemeRegistry
{
    public function __construct(
        private readonly ThemeAuditRecorder $auditRecorder,
        private readonly ThemeSearchIndexer $searchIndexer,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, ThemeDefinitionDto $definition): ThemeDefinitionDto
    {
        $this->assertDefinitionsTablePresent();
        $definition = $this->resolveDefinitionSource($definition);
        $this->assertNotDuplicate($organizationId, $workspaceId, $definition);

        $model = ThemeDefinition::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_id' => $applicationId ?? ThemeMapper::resolveApplicationId($definition->applicationPublicId),
            'module_key' => $definition->moduleKey,
            'theme_key' => $definition->themeKey,
            'name' => $definition->name !== '' ? $definition->name : $definition->themeKey,
            'description' => $definition->description,
            'status' => $definition->status !== '' ? $definition->status : ThemeDefinitionStatus::Draft->value,
            'scope' => $definition->scope !== '' ? $definition->scope : ThemeScope::Workspace->value,
            'inheritance_mode' => $definition->inheritanceMode !== '' ? $definition->inheritanceMode : ThemeInheritanceMode::None->value,
            'parent_theme_id' => ThemeMapper::resolveThemeId($definition->parentThemePublicId),
            'tokens_json' => $definition->tokens,
            'metadata' => $definition->metadata,
        ]);

        $created = ThemeMapper::toDefinition($model);
        $this->auditRecorder->recordDefinitionRegistered($created);
        $this->searchIndexer->indexDefinitionBestEffort($created, $organizationId, $workspaceId);

        return $created;
    }

    /** @return list<ThemeDefinitionDto> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return [];
        }

        $query = ThemeDefinition::query()
            ->orderBy('name')
            ->limit($limit);

        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (ThemeDefinition $model) => ThemeMapper::toDefinition($model))->all();
    }

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $themeKey): ThemeDefinitionDto
    {
        $this->assertDefinitionsTablePresent();

        return ThemeMapper::toDefinition(
            $this->resolveModelByKey($organizationId, $workspaceId, $moduleKey, $themeKey),
        );
    }

    /**
     * @param  ThemeDefinitionDto|array<string, mixed>  $source
     */
    public function registerFromSource(string $organizationId, ?string $workspaceId, ?string $applicationId, mixed $source): ThemeDefinitionDto
    {
        return $this->register($organizationId, $workspaceId, $applicationId, $this->resolveDefinitionSource($source));
    }

    public function resolveModelByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $themeKey): ThemeDefinition
    {
        $this->assertDefinitionsTablePresent();

        $query = ThemeDefinition::query()->where('theme_key', $themeKey);

        if ($moduleKey !== '') {
            $query->where('module_key', $moduleKey);
        }

        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new ThemeNotFoundException(sprintf('Theme [%s.%s] was not found.', $moduleKey, $themeKey));
        }

        return $model;
    }

    public function resolveModelByPublicId(string $organizationId, ?string $workspaceId, string $publicId): ThemeDefinition
    {
        $this->assertDefinitionsTablePresent();

        $query = ThemeDefinition::query()->where('public_id', $publicId);
        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new ThemeNotFoundException(sprintf('Theme definition [%s] was not found.', $publicId));
        }

        return $model;
    }

    /**
     * @param  ThemeDefinitionDto|array<string, mixed>  $source
     */
    private function resolveDefinitionSource(mixed $source): ThemeDefinitionDto
    {
        if ($source instanceof ThemeDefinitionDto) {
            return $source;
        }

        if (is_array($source)) {
            return ThemeDefinitionDto::fromArray($source);
        }

        throw new ThemeRegistryException('Unsupported theme definition source.');
    }

    private function assertNotDuplicate(string $organizationId, ?string $workspaceId, ThemeDefinitionDto $definition): void
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return;
        }

        $query = ThemeDefinition::query()->where('theme_key', $definition->themeKey);

        if ($definition->moduleKey !== null && $definition->moduleKey !== '') {
            $query->where('module_key', $definition->moduleKey);
        }

        ThemeMapper::applyOrganizationScope($query, $organizationId);
        ThemeMapper::applyWorkspaceScope($query, $workspaceId);

        if ($query->exists()) {
            throw new ThemeRegistryException(sprintf(
                'Theme [%s.%s] is already registered.',
                $definition->moduleKey ?? '',
                $definition->themeKey,
            ));
        }
    }

    private function assertDefinitionsTablePresent(): void
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            throw new ThemeValidationException('Theme definitions table is not available.');
        }
    }
}
