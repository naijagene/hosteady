<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Between = 'between';
    case In = 'in';
    case NotIn = 'not_in';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case Regex = 'regex';
}
