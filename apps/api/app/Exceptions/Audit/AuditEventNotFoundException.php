<?php

namespace App\Exceptions\Audit;

class AuditEventNotFoundException extends AuditException
{
    public function __construct(string $message = 'Audit event not found.')
    {
        parent::__construct($message, 404);
    }
}
