<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportColumnType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case DateTime = 'datetime';
    case Boolean = 'boolean';
    case Currency = 'currency';
    case Percentage = 'percentage';
    case Status = 'status';
    case Reference = 'reference';
}
