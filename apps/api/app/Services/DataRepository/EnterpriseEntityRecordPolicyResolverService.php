<?php

namespace App\Services\DataRepository;

use App\Modules\Sdk\DataRepository\Contracts\EntityRecordPolicyResolver;
use App\Services\Authorization\TenantAuthorizationService;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordPolicyResolverService implements EntityRecordPolicyResolver
{
    public function __construct(
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    public function canRead(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.read');
    }

    public function canCreate(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.create');
    }

    public function canUpdate(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.update');
    }

    public function canDelete(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.delete');
    }

    public function canRestore(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.restore');
    }

    public function canLink(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.link');
    }

    public function canQuery(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.read');
    }

    public function canManage(TenantContext $context): bool
    {
        return $this->authorizationService->allows($context, 'data.records.manage');
    }
}
