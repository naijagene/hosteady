<?php

namespace App\Exceptions\Application;

class ApplicationNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Application not found.')
    {
        parent::__construct($message, 404);
    }
}
