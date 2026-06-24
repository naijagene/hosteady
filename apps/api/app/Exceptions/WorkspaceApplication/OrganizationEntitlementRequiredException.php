<?php

namespace App\Exceptions\WorkspaceApplication;

class OrganizationEntitlementRequiredException extends WorkspaceApplicationException
{
    public function __construct()
    {
        parent::__construct('Organization entitlement is required before enabling an application in this workspace.');
    }
}
