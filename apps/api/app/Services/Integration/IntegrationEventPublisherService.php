<?php

namespace App\Services\Integration;

use App\Models\IntegrationEvent;
use App\Modules\Sdk\Integration\Contracts\IntegrationEventPublisher;
use App\Modules\Sdk\Integration\Data\IntegrationEvent as IntegrationEventDto;
use App\Modules\Sdk\Integration\Data\IntegrationEventEnvelope;
use App\Modules\Sdk\Integration\Enums\IntegrationEventStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class IntegrationEventPublisherService implements IntegrationEventPublisher
{
    public function __construct(
        private readonly IntegrationValidationService $validationService,
        private readonly IntegrationAuditRecorder $auditRecorder,
        private readonly IntegrationActivityService $activityService,
    ) {
    }

    public function publish(TenantContext $context, IntegrationEventEnvelope $envelope): IntegrationEventDto
    {
        $this->validationService->validateEnvelope($envelope);

        if (
            ! $envelope->forceRepublish
            && ($envelope->metadata['integration_origin'] ?? false) === true
        ) {
            throw new \App\Modules\Sdk\Integration\Exceptions\IntegrationEventException('Integration-origin events cannot be republished without force_republish.');
        }

        if (! $envelope->forceRepublish && $envelope->idempotencyKey !== null && $envelope->idempotencyKey !== '') {
            $existing = IntegrationEvent::query()
                ->where('organization_id', $context->organization->id)
                ->where('idempotency_key', $envelope->idempotencyKey)
                ->where('event_name', $envelope->eventName)
                ->first();

            if ($existing !== null) {
                return IntegrationMapper::toEvent($existing);
            }
        }

        $sourceType = $envelope->sourceType !== '' ? $envelope->sourceType : 'system';
        if ($sourceType === 'platform') {
            $sourceType = 'system';
        }

        $now = now();
        $model = IntegrationEvent::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'event_name' => $envelope->eventName,
            'event_version' => $envelope->eventVersion,
            'direction' => $envelope->direction !== '' ? $envelope->direction : 'internal',
            'source_type' => $sourceType,
            'source_module_key' => $envelope->sourceModuleKey,
            'source_entity_key' => $envelope->sourceEntityKey,
            'source_public_id' => $envelope->sourcePublicId,
            'correlation_id' => $envelope->correlationId,
            'idempotency_key' => $envelope->idempotencyKey,
            'status' => IntegrationEventStatus::Published->value,
            'payload_json' => $envelope->payload,
            'headers_json' => $envelope->headers,
            'metadata' => array_merge($envelope->metadata, [
                'integration_origin' => true,
            ]),
            'occurred_at' => $now,
            'published_at' => $now,
            'created_at' => $now,
        ]);

        $event = IntegrationMapper::toEvent($model);
        $this->auditRecorder->recordEventPublished($event);
        $this->activityService->logEvent($model, 'published', null, $event->toArray());

        return $event;
    }
}
