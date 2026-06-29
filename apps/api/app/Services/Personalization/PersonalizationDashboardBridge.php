<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationDashboardBridge
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolve(TenantContext $context, array $preferences): array
    {
        return [
            'dashboard_layout' => $preferences['dashboard_layout'] ?? null,
        ];
    }
}
