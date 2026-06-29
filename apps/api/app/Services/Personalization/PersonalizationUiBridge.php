<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationUiBridge
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolveForPage(TenantContext $context, array $preferences, array $pageMetadata = []): array
    {
        return array_replace_recursive(
            [
                'compact_mode' => (bool) ($preferences['compact_mode'] ?? false),
            ],
            is_array($pageMetadata['personalization'] ?? null) ? $pageMetadata['personalization'] : [],
        );
    }
}
