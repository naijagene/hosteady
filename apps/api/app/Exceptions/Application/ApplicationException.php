<?php

namespace App\Exceptions\Application;

use Exception;

abstract class ApplicationException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }
}
