<?php

namespace App\Services\Entity;

use App\Models\EntityComment;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Entity\Exceptions\EntityNotFoundException;

class EnterpriseEntityCommentService
{
    public function __construct(
        private readonly EnterpriseEntityAuditRecorder $auditRecorder,
        private readonly EnterpriseEntitySearchIndexer $searchIndexer,
        private readonly EnterpriseEntityLifecycleService $lifecycleService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        string $moduleKey,
        string $entityKey,
        string $entityPublicId,
        string $commentBody,
        ?string $userId = null,
        ?string $membershipId = null,
        array $metadata = [],
    ): array {
        $model = EntityComment::query()->create([
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'module_key' => $moduleKey,
            'entity_key' => $entityKey,
            'entity_public_id' => $entityPublicId,
            'comment_body' => $commentBody,
            'metadata' => $metadata,
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
        ]);

        $this->auditRecorder->recordCommentCreated($model);
        $this->searchIndexer->indexCommentBestEffort($model, $scope);
        $this->lifecycleService->dispatchCommented($scope, $moduleKey, $entityKey, $entityPublicId, $model->public_id);

        return EnterpriseEntityMapper::toCommentReference($model);
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
        $query = EntityComment::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey)
            ->where('entity_public_id', $entityPublicId)
            ->orderByDesc('created_at');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (EntityComment $model) => EnterpriseEntityMapper::toCommentReference($model))
            ->all();
    }

    public function delete(
        string $organizationId,
        ?string $workspaceId,
        string $commentPublicId,
    ): void {
        $query = EntityComment::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $commentPublicId);

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        $comment = $query->first();

        if ($comment === null) {
            throw new EntityNotFoundException(sprintf('Entity comment [%s] was not found.', $commentPublicId));
        }

        $comment->delete();
        $this->auditRecorder->recordCommentDeleted($comment);
    }
}
