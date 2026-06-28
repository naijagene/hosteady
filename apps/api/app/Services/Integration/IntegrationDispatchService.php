<?php

namespace App\Services\Integration;

use App\Models\IntegrationDispatch;
use App\Models\IntegrationEvent;
use App\Models\IntegrationEndpoint;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchRequest;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchResult;
use App\Modules\Sdk\Integration\Enums\IntegrationDeliveryStatus;
use App\Modules\Sdk\Integration\Exceptions\IntegrationDispatchException;
use Illuminate\Support\Str;

class IntegrationDispatchService
{
    public function __construct(
        private readonly IntegrationEndpointService $endpointService,
        private readonly IntegrationTransformerService $transformerService,
        private readonly IntegrationRetryPolicyService $retryPolicyService,
        private readonly IntegrationDeadLetterService $deadLetterService,
        private readonly IntegrationAuditRecorder $auditRecorder,
    ) {
    }

    public function createPending(
        IntegrationEvent $event,
        ?IntegrationEndpoint $endpoint,
        ?string $subscriptionKey,
        array $metadata = [],
    ): IntegrationDispatch {
        return IntegrationDispatch::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $event->organization_id,
            'workspace_id' => $event->workspace_id,
            'integration_event_id' => $event->id,
            'integration_endpoint_id' => $endpoint?->id,
            'subscription_key' => $subscriptionKey,
            'status' => IntegrationDeliveryStatus::Pending->value,
            'attempt' => 0,
            'max_attempts' => (int) ($metadata['max_attempts'] ?? 3),
            'metadata' => $metadata,
        ]);
    }

    public function dispatch(IntegrationDispatchRequest $request, IntegrationEvent $event): IntegrationDispatchResult
    {
        $endpoint = null;

        if ($request->endpointPublicId !== null && $request->endpointPublicId !== '') {
            $endpoint = $this->endpointService->resolveModel(
                $event->organization_id,
                $event->workspace_id,
                $request->endpointPublicId,
            );
        }

        $dispatch = $this->createPending($event, $endpoint, $request->subscriptionKey, $request->metadata);

        return $this->execute($dispatch->fresh(['event', 'endpoint']));
    }

    public function execute(IntegrationDispatch $dispatch): IntegrationDispatchResult
    {
        $dispatch->loadMissing(['event', 'endpoint']);
        $event = $dispatch->event;

        if ($event === null) {
            throw new IntegrationDispatchException('Dispatch event was not found.');
        }

        $payload = is_array($event->payload_json) ? $event->payload_json : [];
        $transform = is_array($dispatch->metadata) ? ($dispatch->metadata['transform'] ?? []) : [];
        $transformType = (string) ($transform['type'] ?? 'pass_through');
        $transformConfig = is_array($transform['config'] ?? null) ? $transform['config'] : [];

        $body = $this->transformerService->transform($payload, $transformType, $transformConfig);
        $requestPayload = [
            'event_name' => $event->event_name,
            'event_public_id' => $event->public_id,
            'payload' => $body,
            'headers' => is_array($event->headers_json) ? $event->headers_json : [],
        ];

        $dispatch->attempt = (int) $dispatch->attempt + 1;
        $dispatch->dispatched_at = now();
        $dispatch->request_json = $requestPayload;
        $dispatch->status = IntegrationDeliveryStatus::Simulating->value;
        $dispatch->save();

        $endpoint = $dispatch->endpoint;
        $simulated = [
            'status' => 'accepted',
            'endpoint_key' => $endpoint?->endpoint_key,
            'url' => $endpoint?->url_template,
            'simulated' => true,
        ];

        $dispatch->response_json = $simulated;
        $dispatch->status = IntegrationDeliveryStatus::Completed->value;
        $dispatch->completed_at = now();
        $dispatch->save();

        $result = IntegrationMapper::toDispatchResult($dispatch->fresh());
        $this->auditRecorder->recordDispatchCompleted($result);

        return $result;
    }

    public function fail(IntegrationDispatch $dispatch, string $message): IntegrationDispatchResult
    {
        $dispatch->error_message = $message;
        $dispatch->status = IntegrationDeliveryStatus::Failed->value;
        $dispatch->completed_at = now();
        $dispatch->save();

        $retried = $this->retryPolicyService->scheduleRetry($dispatch->fresh());

        if ($retried->status === IntegrationDeliveryStatus::Failed) {
            $this->deadLetterService->enqueue($dispatch->organization_id, $dispatch->workspace_id, [
                'integration_event_id' => $dispatch->integration_event_id,
                'integration_dispatch_id' => $dispatch->id,
                'reason' => 'max_attempts_exceeded',
                'error_message' => $message,
                'payload' => is_array($dispatch->request_json) ? $dispatch->request_json : [],
            ]);
            $dispatch->status = IntegrationDeliveryStatus::DeadLettered->value;
            $dispatch->save();
        }

        $result = IntegrationMapper::toDispatchResult($dispatch->fresh());
        $this->auditRecorder->recordDispatchFailed($result);

        return $result;
    }
}
