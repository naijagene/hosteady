<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationSchedule;

/**
 * Tenant-scoped scheduled notification management.
 */
interface NotificationScheduler
{
    public function schedule(
        string $organizationId,
        ?string $workspaceId,
        string $membershipPublicId,
        NotificationSchedule $schedule,
    ): NotificationSchedule;

    /**
     * @return list<NotificationSchedule>
     */
    public function list(string $organizationId, ?string $workspaceId, string $membershipPublicId): array;

    public function cancel(string $organizationId, ?string $workspaceId, string $schedulePublicId): NotificationSchedule;
}
