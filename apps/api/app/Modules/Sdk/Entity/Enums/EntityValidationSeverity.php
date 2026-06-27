<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
