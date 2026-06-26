<?php

namespace App\Exceptions\Enterprise;

use App\Exceptions\WorkspaceApplication\WorkspaceApplicationException;

class EnterpriseCapabilityDisabledException extends WorkspaceApplicationException
{
    public function __construct(string $capability)
    {
        parent::__construct(
            message: sprintf('Enterprise capability "%s" is disabled for this workspace runtime.', $capability),
            statusCode: 403,
        );
    }
}
