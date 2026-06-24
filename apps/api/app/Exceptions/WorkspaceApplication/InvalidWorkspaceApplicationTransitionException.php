<?php

namespace App\Exceptions\WorkspaceApplication;

class InvalidWorkspaceApplicationTransitionException extends WorkspaceApplicationException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
