<?php

namespace App\Exceptions\Application;

class CoreApplicationProtectedException extends ApplicationException
{
    public function __construct(string $message = 'Core applications cannot be disabled or uninstalled.')
    {
        parent::__construct($message, 422);
    }
}
