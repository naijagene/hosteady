<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class WorkspaceApplicationRuntimeInput
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $dependencies
     */
    public function __construct(
        public string $applicationId,
        public string $workspaceApplicationPublicId,
        public string $organizationApplicationPublicId,
        public string $applicationPublicId,
        public string $key,
        public string $name,
        public string $workspaceApplicationStatus,
        public string $organizationApplicationStatus,
        public string $catalogApplicationStatus,
        public string $enabledVersion,
        public string $catalogVersion,
        public bool $isBootstrap,
        public array $capabilities,
        public array $dependencies,
    ) {
    }
}
