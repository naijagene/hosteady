<?php

namespace App\Exceptions\Application;

class InvalidApplicationTransitionException extends ApplicationException
{
    public function __construct(string $message = 'Invalid application status transition.')
    {
        parent::__construct($message, 422);
    }
}
