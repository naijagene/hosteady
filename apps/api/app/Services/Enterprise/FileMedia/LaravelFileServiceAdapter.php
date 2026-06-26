<?php

namespace App\Services\Enterprise\FileMedia;

use App\Enums\FileVisibility;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\PlatformFile;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Contracts\FileServicePort;
use App\Modules\Sdk\Enterprise\Contracts\StoragePort;
use App\Modules\Sdk\Enterprise\Data\EntityReference;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\FileDownloadResult;
use App\Modules\Sdk\Enterprise\Data\FileReference;
use App\Modules\Sdk\Enterprise\Data\FileUpdateRequest;
use App\Modules\Sdk\Enterprise\Data\FileUploadRequest;
use App\Services\Enterprise\Audit\EnterpriseFileAuditRecorder;
use Illuminate\Support\Str;

class LaravelFileServiceAdapter implements FileServicePort
{
    public function __construct(
        private readonly StoragePort $storagePort,
        private readonly EnterpriseFileAuditRecorder $auditRecorder,
        private readonly FileCategoryClassifier $categoryClassifier,
    ) {
    }

    public function upload(FileUploadRequest $request): FileReference
    {
        $organization = Organization::query()
            ->where('public_id', $request->scope->organizationPublicId)
            ->firstOrFail();

        $workspaceId = null;
        $workspacePublicId = $request->scope->workspacePublicId;

        if ($workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $membership = $this->resolveMembership(
            $organization->id,
            $request->uploadedMembershipPublicId,
        );

        $this->assertUploadAllowed($request);

        $publicId = (string) Str::uuid7();
        $extension = $this->resolveExtension($request->originalFilename, $request->mimeType);
        $category = $this->categoryClassifier->classify($request->mimeType, $extension);
        $filename = $publicId.($extension !== '' ? '.'.$extension : '');
        $visibility = FileVisibility::tryFrom($request->visibility) ?? FileVisibility::Private;
        $disk = $this->resolveDisk($visibility);
        $storagePath = $this->buildStoragePath(
            $organization->public_id,
            $workspacePublicId,
            $request->scope->moduleKey,
            $filename,
        );

        $checksum = hash('sha256', $request->contents);

        if (! $this->storagePort->store($disk, $storagePath, $request->contents)) {
            throw new \RuntimeException('Failed to store uploaded file.');
        }

        $file = PlatformFile::query()->create([
            'organization_id' => $organization->id,
            'workspace_id' => $workspaceId,
            'module_key' => $request->scope->moduleKey,
            'entity_reference' => $request->entityReference?->toArray(),
            'filename' => $filename,
            'original_filename' => $request->originalFilename,
            'extension' => $extension,
            'mime_type' => $request->mimeType,
            'category' => $category,
            'checksum' => $checksum,
            'size_bytes' => $request->sizeBytes,
            'visibility' => $visibility,
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
            'uploaded_by_user_id' => $membership->user_id,
            'uploaded_membership_id' => $membership->id,
            'display_name' => $request->displayName ?? $request->originalFilename,
            'metadata' => $request->metadata,
        ]);

        $reference = $this->toFileReference($file, $membership->public_id);
        $this->auditRecorder->recordUploaded($file);

        return $reference;
    }

    public function update(FileUpdateRequest $request): FileReference
    {
        $file = $this->findModel($request->scope, $request->filePublicId);

        if ($request->displayName !== null) {
            $file->display_name = $request->displayName;
        }

        if ($request->visibility !== null) {
            $visibility = FileVisibility::tryFrom($request->visibility);

            if ($visibility !== null) {
                $file->visibility = $visibility;
            }
        }

        if ($request->entityReference !== null) {
            $file->entity_reference = $request->entityReference->toArray();
        }

        if ($request->metadata !== null) {
            $file->metadata = $request->metadata;
        }

        $file->save();

        $this->auditRecorder->recordUpdated($file);

        return $this->toFileReference($file);
    }

    public function delete(EnterpriseScope $scope, string $filePublicId): void
    {
        $file = $this->findModel($scope, $filePublicId);

        $this->storagePort->delete($file->storage_disk, $file->storage_path);
        $file->delete();

        $this->auditRecorder->recordDeleted($file);
    }

    public function find(EnterpriseScope $scope, string $filePublicId): ?FileReference
    {
        $file = PlatformFile::query()
            ->where('public_id', $filePublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->first();

        return $file !== null ? $this->toFileReference($file) : null;
    }

    /**
     * @return list<FileReference>
     */
    public function listForEntity(EnterpriseScope $scope, EntityReference $entityReference): array
    {
        $query = PlatformFile::query()
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->where('entity_reference->type', $entityReference->type)
            ->where('entity_reference->public_id', $entityReference->publicId);

        if ($entityReference->moduleKey !== null) {
            $query->where('module_key', $entityReference->moduleKey);
        }

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $this->organizationId($scope))
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (PlatformFile $file) => $this->toFileReference($file))
            ->all();
    }

    /**
     * @return list<FileReference>
     */
    public function listForScope(EnterpriseScope $scope, int $limit = 50): array
    {
        $query = PlatformFile::query()
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at');

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $this->organizationId($scope))
                ->value('id');

            $query->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')
                    ->orWhere('workspace_id', $workspaceId);
            });
        }

        if ($scope->moduleKey !== null) {
            $query->where('module_key', $scope->moduleKey);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (PlatformFile $file) => $this->toFileReference($file))
            ->all();
    }

    public function download(EnterpriseScope $scope, string $filePublicId): FileDownloadResult
    {
        $file = $this->findModel($scope, $filePublicId);

        if (! $this->storagePort->exists($file->storage_disk, $file->storage_path)) {
            throw new \RuntimeException('Stored file content is missing.');
        }

        $contents = $this->storagePort->get($file->storage_disk, $file->storage_path);
        $this->auditRecorder->recordDownloaded($file);

        return new FileDownloadResult(
            file: $this->toFileReference($file),
            contents: $contents,
        );
    }

    private function findModel(EnterpriseScope $scope, string $filePublicId): PlatformFile
    {
        return PlatformFile::query()
            ->where('public_id', $filePublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }

    private function resolveMembership(string $organizationId, ?string $membershipPublicId): OrganizationMembership
    {
        $query = OrganizationMembership::query()->where('organization_id', $organizationId);

        if ($membershipPublicId !== null) {
            $query->where('public_id', $membershipPublicId);
        }

        return $query->firstOrFail();
    }

    private function assertUploadAllowed(FileUploadRequest $request): void
    {
        $maxSize = (int) config('heos.enterprise.files.max_upload_size', 10485760);

        if ($request->sizeBytes > $maxSize) {
            throw new \InvalidArgumentException('File exceeds maximum upload size.');
        }

        $allowedTypes = config('heos.enterprise.files.allowed_mime_types', []);

        if ($allowedTypes !== [] && ! in_array($request->mimeType, $allowedTypes, true)) {
            throw new \InvalidArgumentException('File mime type is not allowed.');
        }
    }

    private function resolveExtension(string $originalFilename, string $mimeType): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return $extension;
        }

        return match ($mimeType) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            default => '',
        };
    }

    private function resolveDisk(FileVisibility $visibility): string
    {
        return match ($visibility) {
            FileVisibility::Public => (string) config('heos.enterprise.files.public_disk', 'public'),
            default => (string) config('heos.enterprise.files.default_disk', 'local'),
        };
    }

    private function buildStoragePath(
        string $organizationPublicId,
        ?string $workspacePublicId,
        ?string $moduleKey,
        string $filename,
    ): string {
        $segments = ['heos', $organizationPublicId];

        if ($workspacePublicId !== null) {
            $segments[] = $workspacePublicId;
        }

        if ($moduleKey !== null) {
            $segments[] = $moduleKey;
        }

        $segments[] = $filename;

        return implode('/', $segments);
    }

    private function toFileReference(PlatformFile $file, ?string $membershipPublicId = null): FileReference
    {
        $entityReference = null;

        if (is_array($file->entity_reference) && isset($file->entity_reference['type'])) {
            $entityReference = EntityReference::fromArray($file->entity_reference);
        }

        $membershipPublicId ??= OrganizationMembership::query()
            ->where('id', $file->uploaded_membership_id)
            ->value('public_id');

        return new FileReference(
            publicId: $file->public_id,
            filename: $file->filename,
            originalFilename: $file->original_filename,
            extension: $file->extension,
            mimeType: $file->mime_type,
            sizeBytes: $file->size_bytes,
            visibility: $file->visibility->value,
            category: $file->category->value,
            moduleKey: $file->module_key,
            entityReference: $entityReference,
            displayName: $file->display_name,
            metadata: $file->metadata ?? [],
            checksum: $file->checksum,
            uploadedMembershipPublicId: $membershipPublicId !== null ? (string) $membershipPublicId : null,
            createdAt: $file->created_at?->toIso8601String(),
        );
    }
}
