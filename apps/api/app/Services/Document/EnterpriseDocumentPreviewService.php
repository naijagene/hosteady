<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentPreview;
use App\Models\EnterpriseDocumentVersion;
use App\Modules\Sdk\Document\Contracts\DocumentPreviewProvider;
use App\Modules\Sdk\Document\Data\DocumentPreview;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseDocumentPreviewService implements DocumentPreviewProvider
{
    public function __construct(
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentWorkflowBridge $workflowBridge,
    ) {
    }

    public function request(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
        ?string $versionPublicId = null,
    ): DocumentPreview {
        $document = $this->repository->resolveModel($organizationId, $workspaceId, $documentPublicId);

        $versionModel = $versionPublicId !== null
            ? EnterpriseDocumentMapper::applyWorkspaceScope(
                EnterpriseDocumentVersion::query()
                    ->where('organization_id', $organizationId)
                    ->where('document_public_id', $documentPublicId)
                    ->where('public_id', $versionPublicId),
                $workspaceId,
            )->first()
            : $document->currentVersion;

        $model = EnterpriseDocumentPreview::query()->create([
            'id' => (string) Str::uuid7(),
            'enterprise_document_id' => $document->id,
            'document_public_id' => $document->public_id,
            'version_public_id' => $versionModel?->public_id,
            'enterprise_document_version_id' => $versionModel?->id,
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'status' => 'pending',
            'metadata' => ['placeholder' => true],
        ]);

        $preview = EnterpriseDocumentMapper::toPreview($model);
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        if ($context !== null) {
            $this->auditRecorder->recordPreviewRequested($preview);
            $this->workflowBridge->triggerBestEffort($context, 'document.preview.requested', $preview->toArray());
        }

        return $preview;
    }
}
