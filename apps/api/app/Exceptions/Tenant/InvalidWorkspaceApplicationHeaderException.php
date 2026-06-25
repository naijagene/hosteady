<?php

namespace App\Exceptions\Tenant;

class InvalidWorkspaceApplicationHeaderException extends TenantContextException
{
    public function __construct()
    {
        parent::__construct('The X-HEOS-Application-Id header must be a valid UUID.', 422);
    }
}
