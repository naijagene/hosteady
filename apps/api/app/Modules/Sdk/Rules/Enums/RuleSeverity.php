<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
}
