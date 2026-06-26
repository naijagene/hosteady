<?php

namespace App\Modules\Sdk\Workflow\Enums;

enum WorkflowValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
