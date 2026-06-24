<?php

namespace App\Exceptions\WorkspaceApplication;

class WorkspaceApplicationNotFoundException extends WorkspaceApplicationException
{
    public function __construct()
    {
        parent::__construct('Workspace application not found.', 404);
    }
}
