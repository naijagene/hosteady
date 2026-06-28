<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocument;
use App\Modules\Sdk\Document\Contracts\DocumentRepository;
use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentUpdateRequest;
use App\Modules\Sdk\Document\Enums\DocumentStatus;
use App\Modules\Sdk\Document\Exceptions\DocumentNotFoundException;
use Illuminate\Support\Str;

class EnterpriseDocumentRepositoryService implements DocumentRepository
{
    public function find(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
        bool $withTrashed = false,
    ): ?DocumentReference {
        $query = EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocument::query()
                ->where('organization_id', $organizationId)
                ->where('public_id', $documentPublicId)
                ->with('currentVersion'),
            $workspaceId,
        );

        if ($withTrashed) {
            $query->withTrashed();
        }

        $model = $query->first();

        return $model !== null ? EnterpriseDocumentMapper::toReference($model) : null;
    }

    /**
     * @return list<DocumentReference>
     */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        return EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocument::query()
                ->where('organization_id', $organizationId)
                ->with('currentVersion')
                ->orderByDesc('created_at')
                ->limit($limit),
            $workspaceId,
        )
            ->get()
            ->map(fn (EnterpriseDocument $model) => EnterpriseDocumentMapper::toReference($model))
            ->all();
    }

    public function update(string $organizationId, ?string $workspaceId, DocumentUpdateRequest $request): DocumentReference
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $request->documentPublicId);

        $updates = [
            'metadata' => array_merge(is_array($model->metadata) ? $model->metadata : [], $request->metadata),
        ];

        if ($request->title !== null) {
            $updates['title'] = $request->title;
        }

        if ($request->description !== null) {
            $updates['description'] = $request->description;
        }

        if ($request->status !== null) {
            $updates['status'] = $request->status;
        }

        if ($request->visibility !== null) {
            $updates['visibility'] = $request->visibility;
        }

        if ($request->category !== null) {
            $updates['category'] = $request->category;
        }

        $model->fill($updates);
        $model->save();

        return EnterpriseDocumentMapper::toReference($model->fresh(['currentVersion']));
    }

    public function delete(string $organizationId, ?string $workspaceId, string $documentPublicId): DocumentReference
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $documentPublicId);
        $model->status = DocumentStatus::Deleted;
        $model->save();
        $model->delete();

        $deleted = EnterpriseDocument::query()
            ->withTrashed()
            ->with('currentVersion')
            ->where('public_id', $model->public_id)
            ->firstOrFail();

        return EnterpriseDocumentMapper::toReference($deleted);
    }

    public function resolveModel(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
        bool $withTrashed = false,
    ): EnterpriseDocument {
        $query = EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocument::query()
                ->where('organization_id', $organizationId)
                ->where('public_id', $documentPublicId)
                ->with('currentVersion'),
            $workspaceId,
        );

        if ($withTrashed) {
            $query->withTrashed();
        }

        $model = $query->first();

        if ($model === null) {
            throw new DocumentNotFoundException(sprintf(
                'Document [%s] was not found.',
                $documentPublicId,
            ));
        }

        return $model;
    }

    public function createDocumentRecord(
        string $organizationId,
        ?string $workspaceId,
        string $title,
        ?string $description,
        string $visibility,
        string $category,
        ?string $moduleKey,
        array $metadata,
        ?string $createdByUserId = null,
        ?string $createdByMembershipId = null,
    ): EnterpriseDocument {
        return EnterpriseDocument::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'title' => $title,
            'description' => $description,
            'status' => DocumentStatus::Active->value,
            'visibility' => $visibility,
            'category' => $category,
            'module_key' => $moduleKey,
            'metadata' => $metadata,
            'created_by_user_id' => $createdByUserId,
            'created_by_membership_id' => $createdByMembershipId,
        ]);
    }
}
