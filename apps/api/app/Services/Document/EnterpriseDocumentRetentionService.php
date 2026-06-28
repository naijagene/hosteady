<?php

namespace App\Services\Document;

use App\Modules\Sdk\Document\Contracts\DocumentRetentionPolicy;
use App\Modules\Sdk\Document\Data\DocumentRetentionRule;
use App\Modules\Sdk\Document\Enums\DocumentRetentionAction;

class EnterpriseDocumentRetentionService implements DocumentRetentionPolicy
{
    public function __construct(
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentActivityService $activityService,
    ) {
    }

    public function apply(string $organizationId, ?string $workspaceId, DocumentRetentionRule $rule): DocumentRetentionRule
    {
        $document = $this->repository->resolveModel($organizationId, $workspaceId, $rule->documentPublicId);
        $beforeAction = EnterpriseDocumentMapper::enumValue($document->retention_action, 'none');

        $document->retention_action = $rule->action;
        $document->metadata = array_merge(
            is_array($document->metadata) ? $document->metadata : [],
            $rule->metadata,
            ['retention_applied_at' => now()->toIso8601String()],
        );
        $document->save();

        $this->activityService->log(
            organizationId: $organizationId,
            workspaceId: $workspaceId,
            documentPublicId: $document->public_id,
            enterpriseDocumentId: $document->id,
            action: 'retention_applied',
            beforeState: ['retention_action' => $beforeAction],
            afterState: ['retention_action' => $rule->action],
            metadata: $rule->metadata,
        );

        return new DocumentRetentionRule(
            documentPublicId: $rule->documentPublicId,
            action: $rule->action,
            metadata: array_merge($rule->metadata, [
                'applied' => true,
                'supported_actions' => array_map(
                    fn (DocumentRetentionAction $action) => $action->value,
                    DocumentRetentionAction::cases(),
                ),
            ]),
        );
    }
}
