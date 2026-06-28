<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationMessage;
use App\Modules\Sdk\Notification\Data\NotificationReference;
use App\Support\Tenant\TenantContext;

/**
 * Primary notification repository port for tenant-scoped CRUD and read-state management.
 */
interface NotificationPort
{
    /**
     * Dispatch a notification message within the given tenant context.
     */
    public function send(TenantContext $context, NotificationMessage $message): NotificationReference;

    /**
     * @return list<NotificationReference>
     */
    public function list(TenantContext $context, int $limit = 50): array;

    public function find(TenantContext $context, string $notificationPublicId): ?NotificationReference;

    public function markRead(TenantContext $context, string $notificationPublicId): NotificationReference;

    public function markUnread(TenantContext $context, string $notificationPublicId): NotificationReference;

    public function delete(TenantContext $context, string $notificationPublicId): NotificationReference;
}
