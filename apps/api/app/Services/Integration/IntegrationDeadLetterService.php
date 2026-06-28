<?php

namespace App\Services\Integration;

use App\Models\IntegrationDeadLetter;
use App\Modules\Sdk\Integration\Contracts\IntegrationDeadLetterQueue;
use App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;
use App\Modules\Sdk\Integration\Enums\IntegrationDeadLetterStatus;
use App\Modules\Sdk\Integration\Exceptions\IntegrationReplayException;
use Illuminate\Support\Str;

class IntegrationDeadLetterService implements IntegrationDeadLetterQueue
{
    public function __construct(
        private readonly IntegrationAuditRecorder $auditRecorder,
    ) {
    }

    public function enqueue(string $organizationId, ?string $workspaceId, array $payload): IntegrationDeadLetterRecord
    {
        $model = IntegrationDeadLetter::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'integration_event_id' => $payload['integration_event_id'] ?? null,
            'integration_dispatch_id' => $payload['integration_dispatch_id'] ?? null,
            'status' => IntegrationDeadLetterStatus::Open->value,
            'reason' => (string) ($payload['reason'] ?? 'dispatch_failed'),
            'payload_json' => is_array($payload['payload'] ?? null) ? $payload['payload'] : $payload,
            'error_message' => isset($payload['error_message']) ? (string) $payload['error_message'] : null,
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            'created_at' => now(),
        ]);

        $record = IntegrationMapper::toDeadLetter($model);
        $this->auditRecorder->recordDeadLetterEnqueued($record);

        return $record;
    }

    public function resolve(string $organizationId, ?string $workspaceId, string $publicId): IntegrationDeadLetterRecord
    {
        $query = IntegrationDeadLetter::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId);

        IntegrationMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new IntegrationReplayException(sprintf('Dead letter [%s] was not found.', $publicId));
        }

        $context = app()->bound(\App\Support\Tenant\TenantContext::class)
            ? app(\App\Support\Tenant\TenantContext::class)
            : null;

        $model->fill([
            'status' => IntegrationDeadLetterStatus::Resolved->value,
            'resolved_at' => now(),
            'resolved_by_user_id' => $context?->user->id,
            'resolved_by_membership_id' => $context?->membership->id,
        ])->save();

        $record = IntegrationMapper::toDeadLetter($model->fresh());
        $this->auditRecorder->recordDeadLetterResolved($record);

        return $record;
    }
}
