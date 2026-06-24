<?php

namespace App\Services\Audit;

use App\Enums\AuditActorType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Models\AuditLog;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Http\RequestContext;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditEventRecorder
{
    public function __construct(
        private readonly AuditRedactor $redactor,
    ) {
    }

    public function record(AuditEventData $event): ?AuditLog
    {
        try {
            return $this->persist($event);
        } catch (\Throwable $exception) {
            Log::error('Failed to record audit event.', [
                'action' => $event->action->value,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function persist(AuditEventData $event): AuditLog
    {
        $scope = $event->scope ?? $event->action->defaultScope();
        $severity = $event->severity ?? $event->action->defaultSeverity();
        $retentionClass = $event->retentionClass ?? $event->action->defaultRetention();
        $occurredAt = now();

        [$actorType, $actorUserId, $actorMembershipId, $organizationId, $workspaceId] = $this->resolveActorContext(
            $event,
            $scope,
        );

        if ($scope === AuditScope::Organization && $organizationId === null) {
            throw new \InvalidArgumentException('Organization-scoped audit events require an organization.');
        }

        $auditLog = new AuditLog([
            'id' => (string) Str::uuid7(),
            'occurred_at' => $occurredAt,
            'scope' => $scope,
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'actor_user_id' => $actorUserId,
            'actor_membership_id' => $actorMembershipId,
            'actor_type' => $actorType,
            'ip_address' => $this->requestContext()?->ipAddress,
            'user_agent' => $this->requestContext()?->userAgent,
            'request_id' => $this->requestContext()?->requestId,
            'category' => $event->action->category(),
            'action' => $event->action,
            'event_version' => $event->eventVersion,
            'severity' => $severity,
            'summary' => Str::limit($event->summary, 255, ''),
            'entity_type' => $event->entityType?->value,
            'entity_public_id' => $event->entityPublicId,
            'entity_label' => $event->entityLabel !== null
                ? Str::limit($event->entityLabel, 255, '')
                : null,
            'before_state' => $this->redactor->redact($event->beforeState, $event->entityType),
            'after_state' => $this->redactor->redact($event->afterState, $event->entityType),
            'metadata' => $event->metadata,
            'retention_class' => $retentionClass,
            'expires_at' => $this->resolveExpiresAt($retentionClass, $occurredAt),
            'created_at' => $occurredAt,
        ]);

        $auditLog->save();

        return $auditLog;
    }

    /**
     * @return array{AuditActorType, ?int, ?string, ?string, ?string}
     */
    private function resolveActorContext(AuditEventData $event, AuditScope $scope): array
    {
        if ($event->actorType !== null) {
            return [
                $event->actorType,
                $event->actorUserId,
                $event->actorMembershipId,
                $event->organizationId,
                $event->workspaceId,
            ];
        }

        if (! app()->bound(TenantContext::class)) {
            return [
                AuditActorType::System,
                null,
                null,
                $event->organizationId,
                $event->workspaceId,
            ];
        }

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return [
            AuditActorType::User,
            $event->actorUserId ?? $context->user->id,
            $event->actorMembershipId ?? $context->membership->id,
            $event->organizationId ?? $context->organization->id,
            $event->workspaceId ?? $context->workspace->id,
        ];
    }

    private function resolveExpiresAt(AuditRetentionClass $retentionClass, \DateTimeInterface $occurredAt): ?\Illuminate\Support\Carbon
    {
        $days = $retentionClass->retentionDays();

        if ($days === null) {
            return null;
        }

        return \Illuminate\Support\Carbon::instance($occurredAt)->addDays($days);
    }

    private function requestContext(): ?RequestContext
    {
        if (! app()->bound(RequestContext::class)) {
            return null;
        }

        return app(RequestContext::class);
    }
}
