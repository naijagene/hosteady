<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleSettingDefinition
{
    /**
     * @param  array<string, mixed>|null  $validationRules
     */
    public function __construct(
        public string $settingKey,
        public string $label,
        public ?string $description,
        public string $settingType,
        public mixed $defaultValue,
        public bool $isRequired = false,
        public bool $isSensitive = false,
        public bool $isEncrypted = false,
        public string $scope = 'workspace',
        public ?string $category = null,
        public int $sortOrder = 0,
        public ?array $validationRules = null,
    ) {
    }
}
