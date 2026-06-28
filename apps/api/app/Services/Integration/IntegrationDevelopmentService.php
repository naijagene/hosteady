<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Contracts\IntegrationEventBus;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchResult;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Modules\Sdk\Integration\Data\IntegrationHealthReport;
use App\Modules\Sdk\Integration\Data\IntegrationMappingDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;
use App\Modules\Sdk\Integration\Data\IntegrationReplayResult;
use App\Modules\Sdk\Integration\Data\IntegrationStatistics;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IntegrationDevelopmentService
{
    public function __construct(
        private readonly IntegrationEventBus $eventBus,
        private readonly EnterpriseIntegrationService $integrationService,
        private readonly IntegrationDeadLetterService $deadLetterService,
        private readonly IntegrationHealthService $healthService,
        private readonly IntegrationStatisticsService $statisticsService,
        private readonly IntegrationPermissionService $permissionService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    /** @return list<IntegrationEvent> */
    public function listEvents(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->eventBus->listEvents($context, $limit);
    }

    public function publish(TenantContext $context, IntegrationEventEnvelope $envelope): IntegrationEvent
    {
        $this->requireCapability($context);
        $this->assertPublish($context);

        return $this->eventBus->publish($context, $envelope);
    }

    public function replay(TenantContext $context, IntegrationReplayRequest $request): IntegrationReplayResult
    {
        $this->requireCapability($context);
        $this->assertReplay($context);

        return $this->eventBus->replay($context, $request);
    }

    /** @return list<IntegrationConnectorDefinition> */
    public function listConnectors(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->integrationService->listConnectors($context, $limit);
    }

    public function createConnector(TenantContext $context, IntegrationConnectorDefinition $definition): IntegrationConnectorDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->integrationService->createConnector($context, $definition);
    }

    /** @return list<IntegrationEndpointDefinition> */
    public function listEndpoints(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->integrationService->listEndpoints($context, $limit);
    }

    public function createEndpoint(TenantContext $context, IntegrationEndpointDefinition $definition): IntegrationEndpointDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->integrationService->createEndpoint($context, $definition);
    }

    /** @return list<IntegrationEventSubscription> */
    public function listSubscriptions(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->integrationService->listSubscriptions($context, $limit);
    }

    public function subscribe(TenantContext $context, IntegrationEventSubscription $subscription): IntegrationEventSubscription
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->integrationService->subscribe($context, $subscription);
    }

    /** @return list<IntegrationMappingDefinition> */
    public function listMappings(TenantContext $context, int $limit = 50): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->integrationService->listMappings($context, $limit);
    }

    public function createMapping(TenantContext $context, IntegrationMappingDefinition $definition): IntegrationMappingDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->integrationService->createMapping($context, $definition);
    }

    public function dispatch(TenantContext $context, IntegrationDispatchRequest $request): IntegrationDispatchResult
    {
        $this->requireCapability($context);
        $this->assertDispatch($context);

        return $this->integrationService->dispatch($context, $request);
    }

    public function resolveDeadLetter(TenantContext $context, string $publicId): IntegrationDeadLetterRecord
    {
        $this->requireCapability($context);
        $this->assertAdmin($context);

        return $this->deadLetterService->resolve(
            $context->organization->id,
            $context->workspace?->id,
            $publicId,
        );
    }

    public function health(TenantContext $context): IntegrationHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): IntegrationStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope($context->organization, $context->workspace);
    }

    private function requireCapability(TenantContext $context): void
    {
        if (! (bool) config('heos.enterprise.integrations.enabled', true)) {
            throw new HttpException(503, 'Enterprise integrations are disabled.');
        }

        $this->runtimeBridge->requireCapability($context, 'integrations');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->permissionService->canRead($context)) {
            throw new HttpException(403, 'You do not have permission to read integrations.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->permissionService->canManage($context)) {
            throw new HttpException(403, 'You do not have permission to manage integrations.');
        }
    }

    private function assertPublish(TenantContext $context): void
    {
        if (! $this->permissionService->canPublish($context)) {
            throw new HttpException(403, 'You do not have permission to publish integration events.');
        }
    }

    private function assertDispatch(TenantContext $context): void
    {
        if (! $this->permissionService->canDispatch($context)) {
            throw new HttpException(403, 'You do not have permission to dispatch integration events.');
        }
    }

    private function assertReplay(TenantContext $context): void
    {
        if (! $this->permissionService->canReplay($context)) {
            throw new HttpException(403, 'You do not have permission to replay integration events.');
        }
    }

    private function assertAdmin(TenantContext $context): void
    {
        if (! $this->permissionService->canAdmin($context)) {
            throw new HttpException(403, 'You do not have permission to administer integrations.');
        }
    }
}
