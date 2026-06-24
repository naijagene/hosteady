<?php

namespace App\Exceptions\WorkspaceApplication;

class DuplicateWorkspaceApplicationException extends WorkspaceApplicationException
{
    public function __construct()
    {
        parent::__construct('Application is already enabled in this workspace.');
    }
}
