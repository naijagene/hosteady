<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentVersionReference;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;

interface DocumentVersionManager
{
    public function create(string $organizationId, ?string $workspaceId, DocumentVersionRequest $request): DocumentVersionReference;

    /**
     * @return list<DocumentVersionReference>
     */
    public function list(string $organizationId, ?string $workspaceId, string $documentPublicId): array;

    public function find(string $organizationId, ?string $workspaceId, string $versionPublicId): ?DocumentVersionReference;

    public function restore(string $organizationId, ?string $workspaceId, string $documentPublicId, string $versionPublicId): DocumentVersionReference;

    public function delete(string $organizationId, ?string $workspaceId, string $versionPublicId): DocumentVersionReference;
}
