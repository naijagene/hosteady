<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationEventBus
{
    public function publish(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope $envelope): \App\Modules\Sdk\Integration\Data\IntegrationEvent;

    /** @return list<\App\Modules\Sdk\Integration\Data\IntegrationEvent> */

    public function listEvents(\App\Support\Tenant\TenantContext $context, int $limit = 50): array;

    public function replay(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Integration\Data\IntegrationReplayRequest $request): \App\Modules\Sdk\Integration\Data\IntegrationReplayResult;
}
