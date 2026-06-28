<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition;
use App\Modules\Sdk\Integration\Exceptions\IntegrationConnectorException;
use App\Modules\Sdk\Integration\Exceptions\IntegrationEventException;

class IntegrationValidationService
{
    public function validateEnvelope(IntegrationEventEnvelope $envelope): void
    {
        if ($envelope->eventName === '') {
            throw new IntegrationEventException('Integration event name is required.');
        }

        if ($envelope->direction === '') {
            throw new IntegrationEventException('Integration event direction is required.');
        }
    }

    public function validateConnector(IntegrationConnectorDefinition $definition): void
    {
        if ($definition->connectorKey === '' || $definition->name === '') {
            throw new IntegrationConnectorException('Connector key and name are required.');
        }
    }

    public function validateEndpoint(IntegrationEndpointDefinition $definition): void
    {
        if ($definition->endpointKey === '' || $definition->name === '') {
            throw new IntegrationConnectorException('Endpoint key and name are required.');
        }
    }

    public function validateSubscription(IntegrationEventSubscription $subscription): void
    {
        if ($subscription->subscriptionKey === '' || $subscription->eventPattern === '') {
            throw new IntegrationEventException('Subscription key and event pattern are required.');
        }
    }

    public function validateMapping(IntegrationMappingDefinition $definition): void
    {
        if ($definition->mappingKey === '') {
            throw new IntegrationConnectorException('Mapping key is required.');
        }
    }
}
