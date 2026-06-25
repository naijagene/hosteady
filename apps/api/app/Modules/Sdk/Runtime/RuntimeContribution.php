<?php

namespace App\Modules\Sdk\Runtime;

readonly class RuntimeContribution
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<array<string, mixed>>  $navigation
     * @param  array<string, mixed>  $featureFlags
     * @param  array<string, mixed>  $runtimeMetadata
     * @param  list<array<string, mixed>>  $diagnostics
     * @param  array<string, mixed>  $settingsMetadata
     * @param  list<string>  $dependencies
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $moduleKey,
        public int $priority = 0,
        public array $capabilities = [],
        public array $navigation = [],
        public array $featureFlags = [],
        public array $runtimeMetadata = [],
        public array $diagnostics = [],
        public array $settingsMetadata = [],
        public array $dependencies = [],
        public array $warnings = [],
    ) {
    }

    public static function empty(string $moduleKey, int $priority = 0): self
    {
        return new self(moduleKey: $moduleKey, priority: $priority);
    }
}
