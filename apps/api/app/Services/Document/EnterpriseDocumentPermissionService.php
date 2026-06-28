<?php

namespace App\Services\Document;

use App\Modules\Sdk\Document\Contracts\DocumentPermissionResolver;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentPermissionService implements DocumentPermissionResolver
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.read');
    }

    public function canUpload(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.upload');
    }

    public function canUpdate(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.update');
    }

    public function canDelete(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.delete');
    }

    public function canVersion(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.version');
    }

    public function canAttach(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.attach');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'documents.manage');
    }
}
