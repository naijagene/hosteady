<?php

namespace App\Services\Application;

use App\Enums\WorkspaceSettingType;
use App\Exceptions\WorkspaceApplication\InvalidWorkspaceSettingTypeException;
use App\Services\Application\Data\SettingDefinition;
use App\Services\Application\Data\SettingValidationRule;
use App\Services\WorkspaceApplication\WorkspaceSettingValueValidator;

class ApplicationSettingDefinitionValidator
{
    public function __construct(
        private readonly WorkspaceSettingValueValidator $valueValidator,
    ) {
    }

    public function assertValidValue(SettingDefinition $definition, mixed $value): mixed
    {
        $normalized = $this->valueValidator->assertValid($value, $definition->settingType);
        $this->assertValidationRules($definition->validationRules, $normalized, $definition->settingType);

        return $normalized;
    }

    private function assertValidationRules(
        ?SettingValidationRule $rules,
        mixed $value,
        WorkspaceSettingType $type,
    ): void {
        if ($rules === null) {
            return;
        }

        if ($type === WorkspaceSettingType::String && is_string($value)) {
            if ($rules->minLength !== null && mb_strlen($value) < $rules->minLength) {
                throw new InvalidWorkspaceSettingTypeException('Setting value is shorter than the minimum length.');
            }

            if ($rules->maxLength !== null && mb_strlen($value) > $rules->maxLength) {
                throw new InvalidWorkspaceSettingTypeException('Setting value exceeds the maximum length.');
            }

            if ($rules->pattern !== null && preg_match('/'.$rules->pattern.'/', $value) !== 1) {
                throw new InvalidWorkspaceSettingTypeException('Setting value does not match the required pattern.');
            }
        }

        if (in_array($type, [WorkspaceSettingType::Integer, WorkspaceSettingType::Float], true) && is_numeric($value)) {
            if ($rules->min !== null && $value < $rules->min) {
                throw new InvalidWorkspaceSettingTypeException('Setting value is below the minimum allowed value.');
            }

            if ($rules->max !== null && $value > $rules->max) {
                throw new InvalidWorkspaceSettingTypeException('Setting value exceeds the maximum allowed value.');
            }
        }
    }
}
