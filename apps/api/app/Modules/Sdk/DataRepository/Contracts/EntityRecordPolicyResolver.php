<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Support\Tenant\TenantContext;

interface EntityRecordPolicyResolver
{
    public function canRead(TenantContext $context): bool;

    public function canCreate(TenantContext $context): bool;

    public function canUpdate(TenantContext $context): bool;

    public function canDelete(TenantContext $context): bool;

    public function canRestore(TenantContext $context): bool;

    public function canLink(TenantContext $context): bool;

    public function canQuery(TenantContext $context): bool;

    public function canManage(TenantContext $context): bool;
}
