<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationNavigationBridge
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolve(TenantContext $context, array $preferences): array
    {
        return [
            'sidebar_collapsed' => (bool) ($preferences['sidebar_collapsed'] ?? false),
            'pinned_items' => is_array($preferences['pinned_navigation_items'] ?? null) ? $preferences['pinned_navigation_items'] : [],
        ];
    }
}
