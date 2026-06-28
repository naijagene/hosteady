<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentReference;
use App\Modules\Sdk\Document\Data\DocumentUpdateRequest;

interface DocumentRepository
{
    public function find(string $organizationId, ?string $workspaceId, string $documentPublicId, bool $withTrashed = false): ?DocumentReference;

    /**
     * @return list<DocumentReference>
     */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;

    public function update(string $organizationId, ?string $workspaceId, DocumentUpdateRequest $request): DocumentReference;

    public function delete(string $organizationId, ?string $workspaceId, string $documentPublicId): DocumentReference;
}
