<?php

namespace App\Exceptions\Application;

class ApplicationNotAvailableException extends ApplicationException
{
    public function __construct(string $message = 'Application is not available for installation.')
    {
        parent::__construct($message, 422);
    }
}
