<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
