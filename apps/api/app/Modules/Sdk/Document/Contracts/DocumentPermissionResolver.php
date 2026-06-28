<?php

namespace App\Modules\Sdk\Document\Contracts;

use App\Support\Tenant\TenantContext;

interface DocumentPermissionResolver
{
    public function canRead(TenantContext $context): bool;

    public function canUpload(TenantContext $context): bool;

    public function canUpdate(TenantContext $context): bool;

    public function canDelete(TenantContext $context): bool;

    public function canVersion(TenantContext $context): bool;

    public function canAttach(TenantContext $context): bool;

    public function canManage(TenantContext $context): bool;
}
