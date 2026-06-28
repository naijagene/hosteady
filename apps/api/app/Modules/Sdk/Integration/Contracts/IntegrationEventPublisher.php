<?php

namespace App\Modules\Sdk\Integration\Contracts;

interface IntegrationEventPublisher
{
    public function publish(\App\Support\Tenant\TenantContext $context, \App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope $envelope): \App\Modules\Sdk\Integration\Data\IntegrationEvent;
}
