<?php

namespace App\Services\Integration;

use App\Models\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEvent as IntegrationEventDto;
use App\Modules\Sdk\Integration\Data\IntegrationProcessingResult;

class IntegrationProcessingService
{
    public function __construct(
        private readonly IntegrationEventSubscriptionService $subscriptionService,
        private readonly IntegrationEndpointService $endpointService,
        private readonly IntegrationDispatchService $dispatchService,
    ) {
    }

    public function process(IntegrationEventDto $eventDto, IntegrationEvent $eventModel): IntegrationProcessingResult
    {
        $subscriptions = $this->subscriptionService->matching(
            $eventModel->organization_id,
            $eventModel->workspace_id,
            $eventDto->eventName,
        );

        $dispatches = [];
        $warnings = [];

        foreach ($subscriptions as $subscription) {
            $endpointPublicId = null;

            if ($subscription->endpointKey !== null && $subscription->endpointKey !== '') {
                $endpoint = $this->endpointService->findByKey(
                    $eventModel->organization_id,
                    $eventModel->workspace_id,
                    $subscription->endpointKey,
                );

                if ($endpoint === null) {
                    $warnings[] = [
                        'subscription_key' => $subscription->subscriptionKey,
                        'message' => sprintf('Endpoint [%s] was not found.', $subscription->endpointKey),
                    ];
                    continue;
                }

                $endpointPublicId = $endpoint->publicId;
            }

            $pending = $this->dispatchService->createPending(
                $eventModel,
                $endpointPublicId !== null
                    ? $this->endpointService->resolveModel($eventModel->organization_id, $eventModel->workspace_id, $endpointPublicId)
                    : null,
                $subscription->subscriptionKey,
                [
                    'transform' => [
                        'type' => $subscription->transform['type'] ?? 'pass_through',
                        'config' => $subscription->transform,
                    ],
                    'correlation_id' => $eventDto->correlationId,
                    'max_attempts' => (int) ($subscription->retryPolicy['max_attempts'] ?? 3),
                ],
            );

            $dispatches[] = $this->dispatchService->execute($pending->fresh(['event', 'endpoint']))->toArray();
        }

        return new IntegrationProcessingResult(
            eventPublicId: $eventDto->publicId,
            dispatches: $dispatches,
            warnings: $warnings,
        );
    }
}
