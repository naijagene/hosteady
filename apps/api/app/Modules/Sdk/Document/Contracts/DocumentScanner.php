<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentScanResult;

interface DocumentScanner
{
    public function scan(string $organizationId, ?string $workspaceId, string $documentPublicId): DocumentScanResult;
}
