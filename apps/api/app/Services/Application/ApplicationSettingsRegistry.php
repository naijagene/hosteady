<?php

namespace App\Services\Application;

use App\Enums\SettingDefinitionScope;
use App\Enums\SettingDefinitionStatus;
use App\Models\ApplicationSettingDefinition;
use App\Services\Application\Data\SettingDefinition;
use App\Services\Application\Data\SettingValidationRule;
use Illuminate\Support\Collection;

class ApplicationSettingsRegistry
{
    /**
     * @var array<string, Collection<int, SettingDefinition>>
     */
    private array $cache = [];

    public function hasWorkspaceDefinitions(string $applicationId): bool
    {
        return $this->workspaceDefinitionsForApplication($applicationId)->isNotEmpty();
    }

    /**
     * @return Collection<int, SettingDefinition>
     */
    public function workspaceDefinitionsForApplication(string $applicationId): Collection
    {
        return $this->definitionsForApplication($applicationId, SettingDefinitionScope::Workspace);
    }

    /**
     * @return Collection<int, SettingDefinition>
     */
    public function definitionsForApplication(
        string $applicationId,
        SettingDefinitionScope $scope = SettingDefinitionScope::Workspace,
    ): Collection {
        $cacheKey = $applicationId.':'.$scope->value;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $definitions = ApplicationSettingDefinition::query()
            ->where('application_id', $applicationId)
            ->where('scope', $scope)
            ->where('status', SettingDefinitionStatus::Active)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('setting_key')
            ->get()
            ->map(fn (ApplicationSettingDefinition $definition) => $this->mapDefinition($definition));

        return $this->cache[$cacheKey] = $definitions;
    }

    public function findWorkspaceDefinition(string $applicationId, string $settingKey): ?SettingDefinition
    {
        return $this->workspaceDefinitionsForApplication($applicationId)
            ->firstWhere('settingKey', $settingKey);
    }

    private function mapDefinition(ApplicationSettingDefinition $definition): SettingDefinition
    {
        return new SettingDefinition(
            publicId: $definition->public_id,
            applicationId: $definition->application_id,
            settingKey: $definition->setting_key,
            label: $definition->label,
            description: $definition->description,
            settingType: $definition->setting_type,
            defaultValue: $definition->default_value,
            isRequired: $definition->is_required,
            isSensitive: $definition->is_sensitive,
            isEncrypted: $definition->is_encrypted,
            scope: $definition->scope,
            category: $definition->category,
            sortOrder: $definition->sort_order,
            validationRules: SettingValidationRule::fromArray($definition->validation_rules),
            status: $definition->status,
        );
    }
}
