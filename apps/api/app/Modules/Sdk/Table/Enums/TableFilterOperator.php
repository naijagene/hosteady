<?php

namespace App\Modules\Sdk\Table\Enums;

enum TableFilterOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
}
