<?php

namespace App\Services\Notification;

use App\Models\EnterpriseNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationDigest;
use App\Models\NotificationSchedule;
use App\Models\NotificationSubscription;
use App\Models\NotificationTemplate;
use App\Modules\Sdk\Notification\Data\NotificationStatistics;

class NotificationStatisticsService
{
    public function statisticsForScope(?object $organization = null, ?object $workspace = null): NotificationStatistics
    {
        $notificationsQuery = EnterpriseNotification::query();
        $deliveriesQuery = NotificationDelivery::query();
        $templatesQuery = NotificationTemplate::query();
        $subscriptionsQuery = NotificationSubscription::query();
        $schedulesQuery = NotificationSchedule::query();
        $digestsQuery = NotificationDigest::query();

        if ($organization !== null) {
            $notificationsQuery->where('organization_id', $organization->id);
            $deliveriesQuery->where('organization_id', $organization->id);
            $templatesQuery->where('organization_id', $organization->id);
            $subscriptionsQuery->where('organization_id', $organization->id);
            $schedulesQuery->where('organization_id', $organization->id);
            $digestsQuery->where('organization_id', $organization->id);
        }

        if ($workspace !== null) {
            NotificationMapper::applyWorkspaceScope($notificationsQuery, $workspace->id);
            $deliveriesQuery->where(function ($scoped) use ($workspace) {
                $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id);
            });
            $schedulesQuery->where(function ($scoped) use ($workspace) {
                $scoped->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id);
            });
        }

        return new NotificationStatistics(
            notifications: $notificationsQuery->count(),
            deliveries: $deliveriesQuery->count(),
            templates: $templatesQuery->count(),
            subscriptions: $subscriptionsQuery->count(),
            schedules: $schedulesQuery->count(),
            digests: $digestsQuery->count(),
        );
    }
}
