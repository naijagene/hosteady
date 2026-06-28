<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Modules\Sdk\Document\Data\DocumentUploadRequest;
use App\Modules\Sdk\Document\Data\DocumentVersionRequest;
use App\Support\Tenant\TenantContext;

interface DocumentStorageProvider
{
    public function storeUpload(TenantContext $context, DocumentUploadRequest $request): string;

    public function storeVersion(TenantContext $context, DocumentVersionRequest $request): string;
}
