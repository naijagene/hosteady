<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\AttachmentReference;
use App\Modules\Sdk\Document\Data\AttachmentRequest;

interface AttachmentRepository
{
    public function attach(string $organizationId, ?string $workspaceId, AttachmentRequest $request): AttachmentReference;

    /**
     * @return list<AttachmentReference>
     */
    public function listForDocument(string $organizationId, ?string $workspaceId, string $documentPublicId): array;

    /**
     * @return list<AttachmentReference>
     */
    public function listForSubject(string $organizationId, ?string $workspaceId, string $subjectType, string $subjectPublicId): array;

    public function detach(string $organizationId, ?string $workspaceId, string $attachmentPublicId): AttachmentReference;
}
