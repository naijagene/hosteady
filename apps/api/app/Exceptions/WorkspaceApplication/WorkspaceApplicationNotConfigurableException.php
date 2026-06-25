<?php

namespace App\Exceptions\WorkspaceApplication;

class WorkspaceApplicationNotConfigurableException extends WorkspaceApplicationException
{
    public function __construct()
    {
        parent::__construct('Workspace application must be active to modify settings.');
    }
}
