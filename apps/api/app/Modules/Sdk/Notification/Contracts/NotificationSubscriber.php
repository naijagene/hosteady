<?php

namespace App\Modules\Sdk\Notification\Contracts;

/**
 * Event subscription management for membership-scoped notification routing.
 */
interface NotificationSubscriber
{
    /**
     * Subscribe a membership to an event type with optional channel overrides.
     *
     * @param list<string> $channels
     */
    public function subscribe(
        string $organizationId,
        string $membershipPublicId,
        string $eventType,
        array $channels = [],
    ): void;

    /**
     * Unsubscribe a membership from an event type.
     */
    public function unsubscribe(string $organizationId, string $membershipPublicId, string $eventType): void;
}
