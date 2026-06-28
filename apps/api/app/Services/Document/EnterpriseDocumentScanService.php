<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentScan;
use App\Modules\Sdk\Document\Contracts\DocumentScanner;
use App\Modules\Sdk\Document\Data\DocumentScanResult;
use App\Modules\Sdk\Document\Enums\DocumentScanStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseDocumentScanService implements DocumentScanner
{
    public function __construct(
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentWorkflowBridge $workflowBridge,
    ) {
    }

    public function scan(string $organizationId, ?string $workspaceId, string $documentPublicId): DocumentScanResult
    {
        $document = $this->repository->resolveModel($organizationId, $workspaceId, $documentPublicId);

        $model = EnterpriseDocumentScan::query()->create([
            'id' => (string) Str::uuid7(),
            'enterprise_document_id' => $document->id,
            'document_public_id' => $document->public_id,
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'status' => DocumentScanStatus::Pending->value,
            'metadata' => ['placeholder' => true],
        ]);

        $result = EnterpriseDocumentMapper::toScanResult($model);
        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        if ($context !== null) {
            $this->auditRecorder->recordScanRequested($result);
            $this->workflowBridge->triggerBestEffort($context, 'document.scan.requested', $result->toArray());
        }

        return $result;
    }
}
