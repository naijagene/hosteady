<?php

namespace App\Services\Entity;

use App\Models\EntityActivityLog;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;

class EnterpriseEntityActivityService
{
    public function __construct(
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
        private readonly EnterpriseEntitySearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function log(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $action,
        ?string $entityPublicId = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $actorUserId = null,
        ?string $actorMembershipId = null,
        array $metadata = [],
    ): array {
        $model = EntityActivityLog::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'entity_public_id' => $entityPublicId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'actor_user_id' => $actorUserId,
            'actor_membership_id' => $actorMembershipId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $this->auditRecorder->recordActivityLogged($moduleKey, $entityKey, $action, $entityPublicId);
        $this->searchIndexer->indexActivityBestEffort($model, $scope);

        return EnterpriseEntityMapper::toActivityReference($model);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
    ): array {
        $query = EntityActivityLog::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('entity_public_id', $entityPublicId)
            ->orderByDesc('created_at');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (EntityActivityLog $model) => EnterpriseEntityMapper::toActivityReference($model))
            ->all();
    }
}
