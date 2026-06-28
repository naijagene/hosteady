<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentVersion;
use App\Models\PlatformFile;
use App\Modules\Sdk\Document\Contracts\DocumentStorageProvider;
use App\Modules\Sdk\Document\Contracts\DocumentVersionManager;
use App\Modules\Sdk\Document\Data\DocumentVersionReference;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;
use App\Modules\Sdk\Document\Enums\DocumentVersionStatus;
use App\Modules\Sdk\Document\Exceptions\DocumentNotFoundException;
use App\Modules\Sdk\Document\Exceptions\DocumentVersionException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnterpriseDocumentVersionService implements DocumentVersionManager
{
    public function __construct(
        private readonly EnterpriseDocumentRepositoryService $repository,
        private readonly EnterpriseDocumentActivityService $activityService,
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentSearchIndexer $searchIndexer,
        private readonly EnterpriseDocumentWorkflowBridge $workflowBridge,
    ) {
    }

    public function create(
        string $organizationId,
        ?string $workspaceId,
        DocumentVersionRequest $request,
    ): DocumentVersionReference {
        return DB::transaction(function () use ($organizationId, $workspaceId, $request) {
            $document = $this->repository->resolveModel($organizationId, $workspaceId, $request->documentPublicId);
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            if ($context === null) {
                throw new DocumentVersionException('Tenant context is required to create document versions.');
            }

            $platformFilePublicId = app(DocumentStorageProvider::class)->storeVersion($context, $request);
            $platformFile = PlatformFile::query()
                ->where('public_id', $platformFilePublicId)
                ->firstOrFail();

            $nextVersionNumber = ((int) EnterpriseDocumentVersion::query()
                ->where('enterprise_document_id', $document->id)
                ->max('version_number')) + 1;

            EnterpriseDocumentVersion::query()
                ->where('enterprise_document_id', $document->id)
                ->where('status', DocumentVersionStatus::Active->value)
                ->update(['status' => DocumentVersionStatus::Superseded->value]);

            $version = EnterpriseDocumentVersion::query()->create([
                'id' => (string) Str::uuid7(),
                'enterprise_document_id' => $document->id,
                'document_public_id' => $document->public_id,
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'version_number' => $nextVersionNumber,
                'platform_file_public_id' => $platformFile->public_id,
                'platform_file_id' => $platformFile->id,
                'status' => DocumentVersionStatus::Active->value,
                'label' => $request->label ?? 'v'.$nextVersionNumber,
                'metadata' => $request->metadata,
                'created_by_user_id' => $context->user->id,
                'created_by_membership_id' => $context->membership->id,
                'created_at' => now(),
            ]);

            $document->update(['current_version_id' => $version->id]);

            $reference = EnterpriseDocumentMapper::toVersionReference($version);

            $this->activityService->log(
                organizationId: $organizationId,
                workspaceId: $workspaceId,
                documentPublicId: $document->public_id,
                enterpriseDocumentId: $document->id,
                action: 'versioned',
                afterState: $reference->toArray(),
                userId: $context->user->id,
                membershipId: $context->membership->id,
            );

            $this->auditRecorder->recordVersionCreated($reference);
            $this->searchIndexer->indexDocumentBestEffort(
                EnterpriseDocumentMapper::toReference($document->fresh(['currentVersion'])),
                $context,
            );
            $this->workflowBridge->triggerBestEffort($context, 'document.version.created', $reference->toArray());

            return $reference;
        });
    }

    /**
     * @return list<DocumentVersionReference>
     */
    public function list(string $organizationId, ?string $workspaceId, string $documentPublicId): array
    {
        $this->repository->resolveModel($organizationId, $workspaceId, $documentPublicId);

        $query = EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocumentVersion::query()
                ->where('organization_id', $organizationId)
                ->where('document_public_id', $documentPublicId)
                ->where('status', '!=', DocumentVersionStatus::Deleted->value),
            $workspaceId,
        );

        return $query->orderByDesc('version_number')
            ->get()
            ->map(fn (EnterpriseDocumentVersion $model) => EnterpriseDocumentMapper::toVersionReference($model))
            ->all();
    }

    public function find(string $organizationId, ?string $workspaceId, string $versionPublicId): ?DocumentVersionReference
    {
        $query = EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocumentVersion::query()
                ->where('organization_id', $organizationId)
                ->where('public_id', $versionPublicId)
                ->where('status', '!=', DocumentVersionStatus::Deleted->value),
            $workspaceId,
        );

        $model = $query->first();

        return $model !== null ? EnterpriseDocumentMapper::toVersionReference($model) : null;
    }

    public function restore(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
        string $versionPublicId,
    ): DocumentVersionReference {
        return DB::transaction(function () use ($organizationId, $workspaceId, $documentPublicId, $versionPublicId) {
            $document = $this->repository->resolveModel($organizationId, $workspaceId, $documentPublicId);
            $version = $this->resolveVersionModel($organizationId, $workspaceId, $documentPublicId, $versionPublicId);

            EnterpriseDocumentVersion::query()
                ->where('enterprise_document_id', $document->id)
                ->where('status', DocumentVersionStatus::Active->value)
                ->update(['status' => DocumentVersionStatus::Superseded->value]);

            $version->status = DocumentVersionStatus::Active;
            $version->save();

            $document->update(['current_version_id' => $version->id]);

            $reference = EnterpriseDocumentMapper::toVersionReference($version->fresh());
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            if ($context !== null) {
                $this->activityService->log(
                    organizationId: $organizationId,
                    workspaceId: $workspaceId,
                    documentPublicId: $documentPublicId,
                    enterpriseDocumentId: $document->id,
                    action: 'version_restored',
                    afterState: $reference->toArray(),
                    userId: $context->user->id,
                    membershipId: $context->membership->id,
                );
                $this->auditRecorder->recordVersionRestored($reference);
                $this->workflowBridge->triggerBestEffort($context, 'document.version.restored', $reference->toArray());
            }

            return $reference;
        });
    }

    public function delete(string $organizationId, ?string $workspaceId, string $versionPublicId): DocumentVersionReference
    {
        return DB::transaction(function () use ($organizationId, $workspaceId, $versionPublicId) {
            $version = EnterpriseDocumentMapper::applyWorkspaceScope(
                EnterpriseDocumentVersion::query()
                    ->where('organization_id', $organizationId)
                    ->where('public_id', $versionPublicId)
                    ->where('status', '!=', DocumentVersionStatus::Deleted->value),
                $workspaceId,
            )->first();

            if ($version === null) {
                throw new DocumentNotFoundException(sprintf('Document version [%s] was not found.', $versionPublicId));
            }

            $document = $this->repository->resolveModel($organizationId, $workspaceId, $version->document_public_id);

            if ($document->current_version_id === $version->id) {
                throw new DocumentVersionException('Cannot delete the current document version.');
            }

            $version->status = DocumentVersionStatus::Deleted;
            $version->save();

            $reference = EnterpriseDocumentMapper::toVersionReference($version);
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

            if ($context !== null) {
                $this->auditRecorder->recordVersionDeleted($reference);
            }

            return $reference;
        });
    }

    private function resolveVersionModel(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
        string $versionPublicId,
    ): EnterpriseDocumentVersion {
        $model = EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocumentVersion::query()
                ->where('organization_id', $organizationId)
                ->where('document_public_id', $documentPublicId)
                ->where('public_id', $versionPublicId)
                ->where('status', '!=', DocumentVersionStatus::Deleted->value),
            $workspaceId,
        )->first();

        if ($model === null) {
            throw new DocumentNotFoundException(sprintf('Document version [%s] was not found.', $versionPublicId));
        }

        return $model;
    }
}
