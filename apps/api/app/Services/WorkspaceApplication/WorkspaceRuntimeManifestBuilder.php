<?php

namespace App\Services\WorkspaceApplication;

use App\Services\Application\ApplicationSettingsRegistry;
use App\Services\Application\Data\SettingDefinition;
use App\Services\Application\Data\SettingValidationRule;
use App\Services\Runtime\Data\RuntimeManifest;
use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use App\Services\WorkspaceApplication\Data\RuntimeSettingValue;
use App\Services\WorkspaceApplication\Data\WorkspaceApplicationRuntimeInput;
use App\Services\WorkspaceApplication\Data\WorkspaceSettingRuntimeInput;

class WorkspaceRuntimeManifestBuilder
{
    public function __construct(
        private readonly WorkspaceSettingMasker $masker,
        private readonly ApplicationSettingsRegistry $settingsRegistry,
        private readonly WorkspaceSettingsNormalizer $normalizer,
    ) {
    }

    /**
     * @param  list<WorkspaceApplicationRuntimeInput>  $applications
     * @param  array<string, list<WorkspaceSettingRuntimeInput>>  $settingsByWorkspaceApplicationPublicId
     */
    public function build(array $applications, array $settingsByWorkspaceApplicationPublicId): RuntimeManifest
    {
        $fingerprintApplications = [];
        $resolvedApplications = [];
        $applicationsByPublicId = [];

        usort($applications, fn (WorkspaceApplicationRuntimeInput $left, WorkspaceApplicationRuntimeInput $right) => strcmp($left->key, $right->key));

        foreach ($applications as $application) {
            $workspaceSettings = $settingsByWorkspaceApplicationPublicId[$application->workspaceApplicationPublicId] ?? [];
            $workspaceSettingsByKey = [];

            foreach ($workspaceSettings as $workspaceSetting) {
                $workspaceSettingsByKey[$workspaceSetting->settingKey] = $workspaceSetting;
            }

            $definitions = $this->settingsRegistry
                ->workspaceDefinitionsForApplication($application->applicationId)
                ->keyBy(fn (SettingDefinition $definition) => $definition->settingKey);

            $resolvedSettings = [];
            $fingerprintSettings = [];
            $fingerprintDefinitions = [];

            /** @var SettingDefinition $definition */
            foreach ($definitions as $definition) {
                $fingerprintDefinitions[] = [
                    'setting_key' => $definition->settingKey,
                    'status' => $definition->status->value,
                    'setting_type' => $definition->settingType->value,
                    'default_value_hash' => $this->hashValue($definition->defaultValue, $definition->settingType),
                    'validation_rules_hash' => $this->hashJson($definition->validationRules),
                ];

                $workspaceSetting = $workspaceSettingsByKey[$definition->settingKey] ?? null;

                if ($workspaceSetting !== null) {
                    $resolvedSettings[$definition->settingKey] = $this->mapWorkspaceSetting($workspaceSetting, $definition);
                    $fingerprintSettings[] = $this->fingerprintEffectiveSetting(
                        $definition->settingKey,
                        $workspaceSetting->value,
                        $workspaceSetting->type,
                        $workspaceSetting->version,
                        false,
                    );
                    unset($workspaceSettingsByKey[$definition->settingKey]);

                    continue;
                }

                if ($definition->defaultValue === null) {
                    continue;
                }

                $normalizedDefault = $this->normalizer->normalize($definition->defaultValue, $definition->settingType);
                $resolvedSettings[$definition->settingKey] = $this->mapDefaultSetting($definition, $normalizedDefault);
                $fingerprintSettings[] = $this->fingerprintEffectiveSetting(
                    $definition->settingKey,
                    $normalizedDefault,
                    $definition->settingType->value,
                    0,
                    true,
                );
            }

            foreach ($workspaceSettingsByKey as $workspaceSetting) {
                $resolvedSettings[$workspaceSetting->settingKey] = $this->mapWorkspaceSetting($workspaceSetting);
                $fingerprintSettings[] = $this->fingerprintEffectiveSetting(
                    $workspaceSetting->settingKey,
                    $workspaceSetting->value,
                    $workspaceSetting->type,
                    $workspaceSetting->version,
                    false,
                );
            }

            ksort($resolvedSettings);
            usort($fingerprintSettings, fn (array $left, array $right) => strcmp($left['setting_key'], $right['setting_key']));
            usort($fingerprintDefinitions, fn (array $left, array $right) => strcmp($left['setting_key'], $right['setting_key']));

            $capabilities = $this->sortedList($application->capabilities);
            $dependencies = $this->sortedList($application->dependencies);

            $fingerprintApplications[] = [
                'key' => $application->key,
                'workspace_application_status' => $application->workspaceApplicationStatus,
                'organization_application_status' => $application->organizationApplicationStatus,
                'catalog_application_status' => $application->catalogApplicationStatus,
                'enabled_version' => $application->enabledVersion,
                'catalog_version' => $application->catalogVersion,
                'capabilities' => $capabilities,
                'dependencies' => $dependencies,
                'definitions' => $fingerprintDefinitions,
                'settings' => $fingerprintSettings,
            ];

            $resolved = new ResolvedWorkspaceApplication(
                workspaceApplicationPublicId: $application->workspaceApplicationPublicId,
                organizationApplicationPublicId: $application->organizationApplicationPublicId,
                applicationPublicId: $application->applicationPublicId,
                key: $application->key,
                name: $application->name,
                catalogVersion: $application->catalogVersion,
                enabledVersion: $application->enabledVersion,
                isBootstrap: $application->isBootstrap,
                settings: $resolvedSettings,
                capabilities: $capabilities,
                dependencies: $dependencies,
            );

            $resolvedApplications[] = $resolved;
            $applicationsByPublicId[$application->workspaceApplicationPublicId] = $resolved;
        }

        return new RuntimeManifest(
            fingerprintApplications: $fingerprintApplications,
            applications: $resolvedApplications,
            applicationsByPublicId: $applicationsByPublicId,
        );
    }

    private function mapWorkspaceSetting(
        WorkspaceSettingRuntimeInput $workspaceSetting,
        ?SettingDefinition $definition = null,
    ): RuntimeSettingValue {
        return new RuntimeSettingValue(
            value: $this->masker->maskValue($workspaceSetting->value, $workspaceSetting->isSensitive),
            type: $workspaceSetting->type,
            version: $workspaceSetting->version,
            isSensitive: $workspaceSetting->isSensitive,
            valueRedacted: $this->masker->isRedacted($workspaceSetting->isSensitive),
            isDefault: false,
            definitionPublicId: $definition?->publicId,
            label: $definition?->label,
            category: $definition?->category,
        );
    }

    private function mapDefaultSetting(SettingDefinition $definition, mixed $normalizedDefault): RuntimeSettingValue
    {
        return new RuntimeSettingValue(
            value: $this->masker->maskValue($normalizedDefault, $definition->isSensitive),
            type: $definition->settingType->value,
            version: 0,
            isSensitive: $definition->isSensitive,
            valueRedacted: $this->masker->isRedacted($definition->isSensitive),
            isDefault: true,
            definitionPublicId: $definition->publicId,
            label: $definition->label,
            category: $definition->category,
        );
    }

    /**
     * @return array{setting_key: string, value_hash: string, value_source: string, version: int, setting_type: string}
     */
    private function fingerprintEffectiveSetting(
        string $settingKey,
        mixed $value,
        string $type,
        int $version,
        bool $isDefault,
    ): array {
        return [
            'setting_key' => $settingKey,
            'value_hash' => $this->hashValue($value, \App\Enums\WorkspaceSettingType::from($type)),
            'value_source' => $isDefault ? 'default' : 'workspace',
            'version' => $version,
            'setting_type' => $type,
        ];
    }

    private function hashValue(mixed $value, \App\Enums\WorkspaceSettingType $type): string
    {
        if ($value === null) {
            return 'null';
        }

        return hash('sha256', json_encode($this->normalizer->normalize($value, $type), JSON_THROW_ON_ERROR));
    }

    private function hashJson(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if ($value instanceof SettingValidationRule) {
            return hash('sha256', json_encode([
                'min_length' => $value->minLength,
                'max_length' => $value->maxLength,
                'pattern' => $value->pattern,
                'min' => $value->min,
                'max' => $value->max,
            ], JSON_THROW_ON_ERROR));
        }

        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function sortedList(array $values): array
    {
        $values = array_values($values);
        sort($values);

        return $values;
    }
}
