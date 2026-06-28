<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationPreference;

/**
 * Membership-scoped notification preference storage and retrieval.
 */
interface NotificationPreferenceProvider
{
    /**
     * @return list<NotificationPreference>
     */
    public function get(string $organizationId, string $membershipPublicId): array;

    public function update(string $organizationId, string $membershipPublicId, NotificationPreference $preference): NotificationPreference;
}
