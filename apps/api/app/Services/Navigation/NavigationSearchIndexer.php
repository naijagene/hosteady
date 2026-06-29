<?php

namespace App\Services\Navigation;

use App\Modules\Sdk\Navigation\Data\NavigationDefinition;

class NavigationSearchIndexer
{
    public function indexDefinitionBestEffort(NavigationDefinition $definition, string $organizationId, ?string $workspaceId): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }
        } catch (\Throwable) {
        }
    }
}
