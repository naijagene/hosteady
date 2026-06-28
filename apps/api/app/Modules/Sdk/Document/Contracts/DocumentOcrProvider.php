<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentOcrResult;

interface DocumentOcrProvider
{
    public function request(string $organizationId, ?string $workspaceId, string $documentPublicId): DocumentOcrResult;
}
