<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentOcrResult;
use App\Modules\Sdk\Document\Contracts\DocumentOcrProvider;
use App\Modules\Sdk\Document\Data\DocumentOcrResult;
use App\Modules\Sdk\Document\Enums\DocumentOcrStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseDocumentOcrService implements DocumentOcrProvider
{
    public function __construct(
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentWorkflowBridge $workflowBridge,
    ) {
    }

    public function request(string $organizationId, ?string $workspaceId, string $documentPublicId): DocumentOcrResult
    {
        $document = $this->repository->resolveModel($organizationId, $workspaceId, $documentPublicId);

        $model = EnterpriseDocumentOcrResult::query()->create([
            'id' => (string) Str::uuid7(),
            'enterprise_document_id' => $document->id,
            'document_public_id' => $document->public_id,
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'status' => DocumentOcrStatus::Pending->value,
            'metadata' => ['placeholder' => true],
        ]);

        $result = EnterpriseDocumentMapper::toOcrResult($model);
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        if ($context !== null) {
            $this->auditRecorder->recordOcrRequested($result);
            $this->workflowBridge->triggerBestEffort($context, 'document.ocr.requested', $result->toArray());
        }

        return $result;
    }
}
