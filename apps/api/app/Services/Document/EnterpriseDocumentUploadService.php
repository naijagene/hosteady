<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentVersion;
use App\Models\PlatformFile;
use App\Modules\Sdk\Document\Contracts\DocumentStorageProvider;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentUploadRequest;
use App\Modules\Sdk\Document\Enums\DocumentVersionStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnterpriseDocumentUploadService
{
    public function __construct(
        private readonly DocumentStorageProvider $storageProvider,
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentActivityService $activityService,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentSearchIndexer $searchIndexer,
        private readonly EnterpriseDocumentWorkflowBridge $workflowBridge,
    ) {
    }

    public function upload(TenantContext $context, DocumentUploadRequest $request): DocumentReference
    {
        return DB::transaction(function () use ($context, $request) {
            $platformFilePublicId = $this->storageProvider->storeUpload($context, $request);
            $platformFile = PlatformFile::query()
                ->where('public_id', $platformFilePublicId)
                ->firstOrFail();

            $document = $this->repository->createDocumentRecord(
                organizationId: $context->organization->id,
                workspaceId: $context->workspace?->id,
                title: $request->title,
                description: $request->description,
                visibility: $request->visibility,
                category: $request->category,
                moduleKey: $request->moduleKey,
                metadata: $request->metadata,
                createdByUserId: $context->user->id,
                createdByMembershipId: $context->membership->id,
            );

            $version = EnterpriseDocumentVersion::query()->create([
                'id' => (string) Str::uuid7(),
                'enterprise_document_id' => $document->id,
                'document_public_id' => $document->public_id,
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'version_number' => 1,
                'platform_file_public_id' => $platformFile->public_id,
                'platform_file_id' => $platformFile->id,
                'status' => DocumentVersionStatus::Active->value,
                'label' => 'v1',
                'metadata' => ['source' => 'upload'],
                'created_by_user_id' => $context->user->id,
                'created_by_membership_id' => $context->membership->id,
                'created_at' => now(),
            ]);

            $document->update(['current_version_id' => $version->id]);

            $reference = EnterpriseDocumentMapper::toReference($document->fresh(['currentVersion']));

            $this->activityService->log(
                organizationId: $context->organization->id,
                workspaceId: $context->workspace?->id,
                documentPublicId: $reference->publicId,
                enterpriseDocumentId: $document->id,
                action: 'uploaded',
                afterState: $reference->toArray(),
                userId: $context->user->id,
                membershipId: $context->membership->id,
            );

            $this->auditRecorder->recordUploaded($reference);
            $this->searchIndexer->indexDocumentBestEffort($reference, $context);
            $this->workflowBridge->triggerBestEffort($context, 'document.uploaded', $reference->toArray());

            try {
                app(\App\Services\Notification\NotificationDocumentBridge::class)
                    ->notifyDocumentEventBestEffort($context, 'uploaded', $reference);
            } catch (\Throwable) {
            }

            return $reference;
        });
    }
}
