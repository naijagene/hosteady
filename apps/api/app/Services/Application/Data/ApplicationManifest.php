<?php

namespace App\Services\Application\Data;

readonly class ApplicationManifest
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $dependencies
     * @param  list<SettingDefinition>  $settingDefinitions
     */
    public function __construct(
        public string $applicationPublicId,
        public string $key,
        public string $name,
        public string $catalogVersion,
        public array $capabilities,
        public array $dependencies,
        public array $settingDefinitions,
    ) {
    }
}
