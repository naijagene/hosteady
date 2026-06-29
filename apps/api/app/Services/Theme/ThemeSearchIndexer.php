<?php

namespace App\Services\Theme;

use App\Modules\Sdk\Theme\Data\ThemeDefinition;

class ThemeSearchIndexer
{
    public function indexDefinitionBestEffort(ThemeDefinition $definition, string $organizationId, ?string $workspaceId): void
    {
        // Theme framework currently stores metadata only; no external indexing.
    }
}
