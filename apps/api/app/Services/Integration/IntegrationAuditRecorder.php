<?php

namespace App\Services\Integration;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Integration\Data\IntegrationConnectorDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;
use App\Modules\Sdk\Integration\Data\IntegrationDispatchResult;
use App\Modules\Sdk\Integration\Data\IntegrationEndpointDefinition;
use App\Modules\Sdk\Integration\Data\IntegrationEvent;
use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;

class IntegrationAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordEventPublished(IntegrationEvent $event): void
    {
        $this->record($event->publicId, AuditAction::IntegrationEventPublished, 'Integration event published', $event->toArray());
    }

    public function recordEventReplayed(IntegrationEvent $event, IntegrationEvent $replayEvent): void
    {
        $this->record($replayEvent->publicId, AuditAction::IntegrationEventReplayed, 'Integration event replayed', [
            'source_event_public_id' => $event->publicId,
            'replay_event_public_id' => $replayEvent->publicId,
        ]);
    }

    public function recordConnectorCreated(IntegrationConnectorDefinition $connector): void
    {
        $this->record($connector->publicId, AuditAction::IntegrationConnectorCreated, 'Integration connector created', $connector->toArray());
    }

    public function recordEndpointCreated(IntegrationEndpointDefinition $endpoint): void
    {
        $this->record($endpoint->publicId, AuditAction::IntegrationEndpointCreated, 'Integration endpoint created', $endpoint->toArray());
    }

    public function recordSubscriptionCreated(IntegrationEventSubscription $subscription): void
    {
        $this->record($subscription->publicId, AuditAction::IntegrationSubscriptionCreated, 'Integration subscription created', $subscription->toArray());
    }

    public function recordDispatchCompleted(IntegrationDispatchResult $dispatch): void
    {
        $this->record($dispatch->publicId, AuditAction::IntegrationDispatchCompleted, 'Integration dispatch completed', $dispatch->toArray());
    }

    public function recordDispatchFailed(IntegrationDispatchResult $dispatch): void
    {
        $this->record($dispatch->publicId, AuditAction::IntegrationDispatchFailed, 'Integration dispatch failed', $dispatch->toArray());
    }

    public function recordDeadLetterEnqueued(IntegrationDeadLetterRecord $record): void
    {
        $this->record($record->publicId, AuditAction::IntegrationDeadLetterEnqueued, 'Integration dead letter enqueued', $record->toArray());
    }

    public function recordDeadLetterResolved(IntegrationDeadLetterRecord $record): void
    {
        $this->record($record->publicId, AuditAction::IntegrationDeadLetterResolved, 'Integration dead letter resolved', $record->toArray());
    }

    private function record(string $entityPublicId, AuditAction $action, string $summary, array $metadata, ?TenantContext $context = null): void
    {
        try {
            $context ??= app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Organization,
                organizationId: $context?->organization->id,
                workspaceId: $context?->workspace?->id,
                entityType: AuditEntityType::EnterpriseIntegration,
                entityPublicId: $entityPublicId,
                actorType: $context ? AuditActorType::User : AuditActorType::System,
                actorUserId: $context?->user->id,
                actorMembershipId: $context?->membership->id,
                severity: AuditSeverity::Info,
                retentionClass: AuditRetentionClass::Ephemeral,
                metadata: $metadata,
            ));
        } catch (\Throwable) {
        }
    }
}
