<?php

namespace App\Modules\Sdk\Exceptions;

use LogicException;

class ModuleSyncNotAvailableException extends LogicException
{
    public function __construct()
    {
        parent::__construct('Module database sync is not available until M3-011 Slice 2.');
    }
}
