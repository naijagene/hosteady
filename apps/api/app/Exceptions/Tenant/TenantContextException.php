<?php

namespace App\Exceptions\Tenant;

use Exception;

class TenantContextException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 403,
    ) {
        parent::__construct($message);
    }
}
