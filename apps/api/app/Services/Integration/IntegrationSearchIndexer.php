<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Integration\Data\IntegrationEvent;

class IntegrationSearchIndexer
{
    public function indexEventBestEffort(IntegrationEvent $event, EnterpriseScope $scope): void
    {
        try {
            if (! (bool) config('heos.enterprise.search.enabled', true)) {
                return;
            }
        } catch (\Throwable) {
        }
    }
}
