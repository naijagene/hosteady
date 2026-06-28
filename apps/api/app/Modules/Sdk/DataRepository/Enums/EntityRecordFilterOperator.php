<?php

namespace App\Modules\Sdk\DataRepository\Enums;

enum EntityRecordFilterOperator: string
{
    case Equals = 'eq';
    case NotEquals = 'neq';
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case In = 'in';
    case NotIn = 'not_in';
    case IsNull = 'is_null';
    case IsNotNull = 'is_not_null';
}