<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardFilterOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case Between = 'between';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
}
