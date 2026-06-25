<?php

namespace App\Exceptions\WorkspaceApplication;

class InvalidWorkspaceSettingTypeException extends WorkspaceApplicationException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
