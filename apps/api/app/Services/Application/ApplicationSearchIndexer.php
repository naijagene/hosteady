<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Data\ApplicationDefinition;

class ApplicationSearchIndexer
{
    public function indexApplicationBestEffort(ApplicationDefinition $application, string $organizationId, ?string $workspaceId): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }
        } catch (\Throwable) {
        }
    }
}
