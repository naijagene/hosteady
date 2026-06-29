<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationRegistry
{
    public function register(string $organizationId, ?string $workspaceId, ?string $applicationId, \App\Modules\Sdk\Navigation\Data\NavigationDefinition $definition): \App\Modules\Sdk\Navigation\Data\NavigationDefinition;

    /** @return list<\App\Modules\Sdk\Navigation\Data\NavigationDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function findByKey(string $organizationId, ?string $workspaceId, string $moduleKey, string $navigationKey): \App\Modules\Sdk\Navigation\Data\NavigationDefinition;
}
