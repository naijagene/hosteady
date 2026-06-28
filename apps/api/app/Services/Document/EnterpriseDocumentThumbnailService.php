<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentThumbnail;
use App\Models\EnterpriseDocumentVersion;
use App\Modules\Sdk\Document\Contracts\DocumentThumbnailProvider;
use App\Modules\Sdk\Document\Data\DocumentThumbnail;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseDocumentThumbnailService implements DocumentThumbnailProvider
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
    ): DocumentThumbnail {
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

        $model = EnterpriseDocumentThumbnail::query()->create([
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

        $thumbnail = EnterpriseDocumentMapper::toThumbnail($model);
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        if ($context !== null) {
            $this->auditRecorder->recordThumbnailRequested($thumbnail);
            $this->workflowBridge->triggerBestEffort($context, 'document.thumbnail.requested', $thumbnail->toArray());
        }

        return $thumbnail;
    }
}
