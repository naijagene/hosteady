<?php

namespace App\Services\Integration;

use App\Models\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Data\IntegrationReplayRequest;
use App\Modules\Sdk\Integration\Data\IntegrationReplayResult;
use App\Modules\Sdk\Integration\Exceptions\IntegrationReplayException;
use App\Support\Tenant\TenantContext;

class IntegrationReplayService
{
    public function __construct(
        private readonly IntegrationEventPublisherService $publisherService,
        private readonly IntegrationProcessingService $processingService,
        private readonly IntegrationAuditRecorder $auditRecorder,
    ) {
    }

    public function replay(TenantContext $context, IntegrationReplayRequest $request): IntegrationReplayResult
    {
        $source = IntegrationEvent::query()
            ->where('organization_id', $context->organization->id)
            ->where('public_id', $request->eventPublicId)
            ->first();

        if ($source === null) {
            throw new IntegrationReplayException(sprintf('Event [%s] was not found.', $request->eventPublicId));
        }

        $sourceDto = IntegrationMapper::toEvent($source);

        $replayEnvelope = IntegrationEventEnvelope::fromArray(array_merge($sourceDto->toArray(), [
            'force_republish' => true,
            'metadata' => array_merge($sourceDto->metadata, $request->metadata, [
                'replayed_from' => $sourceDto->publicId,
            ]),
        ]));

        $replayEvent = $this->publisherService->publish($context, $replayEnvelope);
        $replayModel = IntegrationEvent::query()->where('public_id', $replayEvent->publicId)->firstOrFail();
        $processing = $this->processingService->process($replayEvent, $replayModel);

        $this->auditRecorder->recordEventReplayed($sourceDto, $replayEvent);

        return new IntegrationReplayResult(
            eventPublicId: $sourceDto->publicId,
            replayEventPublicId: $replayEvent->publicId,
            status: 'replayed',
            dispatches: $processing->dispatches,
            metadata: $request->metadata,
        );
    }
}
