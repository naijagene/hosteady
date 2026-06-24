<?php

namespace App\Exceptions\WorkspaceApplication;

use Exception;

abstract class WorkspaceApplicationException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }
}
