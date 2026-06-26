<?php

namespace App\Modules\Sdk\Workflow\Designer\Enums;

enum WorkflowDesignerIssueSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
