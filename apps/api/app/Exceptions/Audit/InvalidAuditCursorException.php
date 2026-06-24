<?php

namespace App\Exceptions\Audit;

class InvalidAuditCursorException extends AuditException
{
    public function __construct(string $message = 'The cursor is invalid or expired.')
    {
        parent::__construct($message, 422);
    }
}
