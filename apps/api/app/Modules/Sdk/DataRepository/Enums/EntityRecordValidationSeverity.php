<?php

namespace App\Modules\Sdk\DataRepository\Enums;

enum EntityRecordValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}