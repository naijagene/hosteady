<?php

namespace App\Exceptions\Application;

class ApplicationAlreadyInstalledException extends ApplicationException
{
    public function __construct(string $message = 'Application is already installed for this organization.')
    {
        parent::__construct($message, 409);
    }
}
