<?php

namespace App\Exceptions\WorkspaceApplication;

class RuntimeUnavailableException extends WorkspaceApplicationException
{
    public function __construct()
    {
        parent::__construct('Runtime unavailable.', 503);
    }
}
