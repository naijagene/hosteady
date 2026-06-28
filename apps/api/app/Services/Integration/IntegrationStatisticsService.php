<?php

namespace App\Services\Integration;

use App\Models\Organization;
use App\Models\IntegrationConnector;
use App\Models\IntegrationDeadLetter;
use App\Models\IntegrationDispatch;
use App\Models\IntegrationEndpoint;
use App\Models\IntegrationEvent;
use App\Models\IntegrationEventSubscription;
use App\Models\Workspace;
use App\Modules\Sdk\Integration\Data\IntegrationStatistics;

class IntegrationStatisticsService
{
    public function statisticsForScope(?Organization $organization, ?Workspace $workspace): IntegrationStatistics
    {
        if ($organization === null) {
            return new IntegrationStatistics(0, 0, 0, 0, 0, 0);
        }

        $workspaceId = $workspace?->id;

        return new IntegrationStatistics(
            events: $this->countScoped(IntegrationEvent::query(), $organization->id, $workspaceId),
            subscriptions: $this->countScoped(IntegrationEventSubscription::query(), $organization->id, $workspaceId),
            connectors: $this->countScoped(IntegrationConnector::query(), $organization->id, $workspaceId),
            endpoints: $this->countScoped(IntegrationEndpoint::query(), $organization->id, $workspaceId),
            dispatches: $this->countScoped(IntegrationDispatch::query(), $organization->id, $workspaceId),
            deadLetters: $this->countScoped(IntegrationDeadLetter::query(), $organization->id, $workspaceId),
        );
    }

    private function countScoped($query, string $organizationId, ?string $workspaceId): int
    {
        IntegrationMapper::applyOrganizationScope($query, $organizationId);
        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        return (int) $query->count();
    }
}
