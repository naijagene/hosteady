<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentThumbnail;

interface DocumentThumbnailProvider
{
    public function request(string $organizationId, ?string $workspaceId, string $documentPublicId, ?string $versionPublicId = null): DocumentThumbnail;
}
