<?php

namespace App\Modules\Sdk\Application\Contracts;

interface WorkspaceProvider
{
    /** @return list<\App\Modules\Sdk\Application\Data\ApplicationWorkspace> */
    public function workspaces(\App\Support\Tenant\TenantContext $context): array;
}
