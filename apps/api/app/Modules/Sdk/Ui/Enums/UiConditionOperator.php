<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
    case HasPermission = 'has_permission';
    case HasRole = 'has_role';
    case FeatureEnabled = 'feature_enabled';
}
