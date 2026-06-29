<?php

namespace App\Services\Personalization;

use App\Support\Tenant\TenantContext;

class PersonalizationNotificationBridge
{
    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    public function resolve(TenantContext $context, array $preferences): array
    {
        return [
            'notifications_panel_position' => $preferences['notifications_panel_position'] ?? null,
        ];
    }
}
