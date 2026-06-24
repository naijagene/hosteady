<?php

namespace App\Exceptions\Application;

class OrganizationApplicationNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Organization application not found.')
    {
        parent::__construct($message, 404);
    }
}
