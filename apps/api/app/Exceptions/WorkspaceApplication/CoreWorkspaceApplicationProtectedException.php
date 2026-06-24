<?php

namespace App\Exceptions\WorkspaceApplication;

class CoreWorkspaceApplicationProtectedException extends WorkspaceApplicationException
{
    public function __construct()
    {
        parent::__construct('Core workspace applications cannot be disabled, archived, or removed.');
    }
}
