<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Data\UiPageDefinition;

class UiSearchIndexer
{
    public function indexPageBestEffort(UiPageDefinition $page, string $organizationId, ?string $workspaceId): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }
        } catch (\Throwable) {
        }
    }
}
