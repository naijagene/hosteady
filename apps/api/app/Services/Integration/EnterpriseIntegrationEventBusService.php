<?php

namespace App\Services\Integration;

use App\Models\IntegrationEvent;
use App\Modules\Sdk\Integration\Contracts\IntegrationEventBus;
use App\Modules\Sdk\Integration\Data\IntegrationEvent as IntegrationEventDto;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;
use App\Modules\Sdk\Integration\Data\IntegrationReplayResult;
use App\Support\Tenant\TenantContext;

class EnterpriseIntegrationEventBusService implements IntegrationEventBus
{
    public function __construct(
        private readonly IntegrationEventPublisherService $publisherService,
        private readonly IntegrationProcessingService $processingService,
        private readonly IntegrationReplayService $replayService,
        private readonly IntegrationEventBusBridge $eventBusBridge,
    ) {
    }

    public function publish(TenantContext $context, IntegrationEventEnvelope $envelope): IntegrationEventDto
    {
        $event = $this->publisherService->publish($context, $envelope);
        $model = IntegrationEvent::query()->where('public_id', $event->publicId)->firstOrFail();
        $this->processingService->process($event, $model);
        $this->eventBusBridge->forwardToPlatformEventBusBestEffort($context, $event);

        return $event;
    }

    public function listEvents(TenantContext $context, int $limit = 50): array
    {
        $query = IntegrationEvent::query()
            ->where('organization_id', $context->organization->id)
            ->orderByDesc('created_at')
            ->limit($limit);

        IntegrationMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->get()->map(fn (IntegrationEvent $model) => IntegrationMapper::toEvent($model))->all();
    }

    public function replay(TenantContext $context, IntegrationReplayRequest $request): IntegrationReplayResult
    {
        return $this->replayService->replay($context, $request);
    }
}
