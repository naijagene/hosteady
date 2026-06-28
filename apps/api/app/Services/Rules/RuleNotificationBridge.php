<?php

namespace App\Services\Rules;

use App\Support\Tenant\TenantContext;

class RuleNotificationBridge
{
    public function notifyRuleEventBestEffort(TenantContext $context, string $eventName, array $metadata = []): void
    {
        try {
            if (! (bool) config('heos.enterprise.business_rules.enabled', true)) {
                return;
            }

            app(\App\Services\Notification\NotificationEntityBridge::class);
        } catch (\Throwable) {
        }
    }
}
