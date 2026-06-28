<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationTemplate;

/**
 * Tenant-scoped template repository for notification content definitions.
 */
interface NotificationTemplateProvider
{
    public function find(string $organizationId, ?string $workspaceId, string $templatePublicId): ?NotificationTemplate;

    /**
     * @return list<NotificationTemplate>
     */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function create(string $organizationId, ?string $workspaceId, NotificationTemplate $template): NotificationTemplate;

    public function update(string $organizationId, ?string $workspaceId, NotificationTemplate $template): NotificationTemplate;
}
