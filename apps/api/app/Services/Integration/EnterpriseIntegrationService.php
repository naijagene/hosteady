<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Contracts\IntegrationEventBus;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchResult;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;
use App\Modules\Sdk\Integration\Data\IntegrationReplayResult;
use App\Support\Tenant\TenantContext;

class EnterpriseIntegrationService
{
    public function __construct(
        private readonly IntegrationEventBus $eventBus,
        private readonly IntegrationConnectorService $connectorService,
        private readonly IntegrationEndpointService $endpointService,
        private readonly IntegrationMappingService $mappingService,
        private readonly IntegrationEventSubscriptionService $subscriptionService,
        private readonly IntegrationDispatchService $dispatchService,
    ) {
    }

    public function publish(TenantContext $context, IntegrationEventEnvelope $envelope): IntegrationEvent
    {
        return $this->eventBus->publish($context, $envelope);
    }

    /** @return list<IntegrationEvent> */
    public function listEvents(TenantContext $context, int $limit = 50): array
    {
        return $this->eventBus->listEvents($context, $limit);
    }

    public function replay(TenantContext $context, IntegrationReplayRequest $request): IntegrationReplayResult
    {
        return $this->eventBus->replay($context, $request);
    }

    /** @return list<IntegrationConnectorDefinition> */
    public function listConnectors(TenantContext $context, int $limit = 50): array
    {
        return $this->connectorService->list($context->organization->id, $context->workspace?->id, $limit);
    }

    public function createConnector(TenantContext $context, IntegrationConnectorDefinition $definition): IntegrationConnectorDefinition
    {
        return $this->connectorService->create($context->organization->id, $context->workspace?->id, $definition);
    }

    /** @return list<IntegrationEndpointDefinition> */
    public function listEndpoints(TenantContext $context, int $limit = 50): array
    {
        return $this->endpointService->list($context->organization->id, $context->workspace?->id, $limit);
    }

    public function createEndpoint(TenantContext $context, IntegrationEndpointDefinition $definition): IntegrationEndpointDefinition
    {
        return $this->endpointService->create($context->organization->id, $context->workspace?->id, $definition);
    }

    /** @return list<IntegrationMappingDefinition> */
    public function listMappings(TenantContext $context, int $limit = 50): array
    {
        return $this->mappingService->list($context->organization->id, $context->workspace?->id, $limit);
    }

    public function createMapping(TenantContext $context, IntegrationMappingDefinition $definition): IntegrationMappingDefinition
    {
        return $this->mappingService->create($context->organization->id, $context->workspace?->id, $definition);
    }

    /** @return list<IntegrationEventSubscription> */
    public function listSubscriptions(TenantContext $context, int $limit = 50): array
    {
        return $this->subscriptionService->listSubscriptions($context->organization->id, $context->workspace?->id, $limit);
    }

    public function subscribe(TenantContext $context, IntegrationEventSubscription $subscription): IntegrationEventSubscription
    {
        return $this->subscriptionService->subscribe($context->organization->id, $context->workspace?->id, $subscription);
    }

    public function dispatch(TenantContext $context, IntegrationDispatchRequest $request): IntegrationDispatchResult
    {
        $event = \App\Models\IntegrationEvent::query()
            ->where('organization_id', $context->organization->id)
            ->where('public_id', $request->eventPublicId)
            ->firstOrFail();

        return $this->dispatchService->dispatch($request, $event);
    }
}
