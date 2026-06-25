<?php

namespace App\Services\Application\Data;

use App\Enums\SettingDefinitionScope;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceSettingType;

readonly class SettingDefinition
{
    public function __construct(
        public string $publicId,
        public string $applicationId,
        public string $settingKey,
        public string $label,
        public ?string $description,
        public WorkspaceSettingType $settingType,
        public mixed $defaultValue,
        public bool $isRequired,
        public bool $isSensitive,
        public bool $isEncrypted,
        public SettingDefinitionScope $scope,
        public ?string $category,
        public int $sortOrder,
        public ?SettingValidationRule $validationRules,
        public SettingDefinitionStatus $status,
    ) {
    }
}
