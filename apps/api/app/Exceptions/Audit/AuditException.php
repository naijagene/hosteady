<?php

namespace App\Exceptions\Audit;

use Exception;

abstract class AuditException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 422,
    ) {
        parent::__construct($message);
    }
}
