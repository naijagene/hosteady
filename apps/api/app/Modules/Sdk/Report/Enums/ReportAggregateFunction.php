<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportAggregateFunction: string
{
    case Count = 'count';
    case Sum = 'sum';
    case Avg = 'avg';
    case Min = 'min';
    case Max = 'max';
    case DistinctCount = 'distinct_count';
}
