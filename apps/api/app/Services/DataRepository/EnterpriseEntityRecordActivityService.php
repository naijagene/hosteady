<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecordActivity;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseEntityRecordActivityService
{
    public function __construct(
        private readonly EnterpriseEntityRecordAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
    ): array {
        $query = EnterpriseEntityRecordActivity::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('record_public_id', $recordPublicId);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (EnterpriseEntityRecordActivity $model) => EnterpriseEntityRecordMapper::toActivityReference($model))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function log(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $recordPublicId,
        string $action,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $userId = null,
        ?string $membershipId = null,
        array $metadata = [],
    ): array {
        $model = EnterpriseEntityRecordActivity::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'record_public_id' => $recordPublicId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'actor_user_id' => $userId,
            'actor_membership_id' => $membershipId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $reference = EnterpriseEntityRecordMapper::toActivityReference($model);
        $this->auditRecorder->recordActivityLogged($moduleKey, $entityKey, $action, $recordPublicId);

        return $reference;
    }
}
