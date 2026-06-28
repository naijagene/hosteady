<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentPreview;

interface DocumentPreviewProvider
{
    public function request(string $organizationId, ?string $workspaceId, string $documentPublicId, ?string $versionPublicId = null): DocumentPreview;
}
