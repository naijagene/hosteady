<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleLifecycleContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $moduleKey,
        public string $organizationPublicId,
        public ?string $workspacePublicId,
        public ?string $applicationKey = null,
        public ?string $applicationPublicId = null,
        public array $metadata = [],
    ) {
    }
}
