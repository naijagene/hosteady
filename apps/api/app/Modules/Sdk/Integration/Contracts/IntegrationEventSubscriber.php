<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationEventSubscriber
{
    /** @return list<\App\Modules\Sdk\Integration\Data\IntegrationEventSubscription> */

    public function listSubscriptions(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function subscribe(string $organizationId, ?string $workspaceId, \App\Modules\Sdk\Integration\Data\IntegrationEventSubscription $subscription): \App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
}
