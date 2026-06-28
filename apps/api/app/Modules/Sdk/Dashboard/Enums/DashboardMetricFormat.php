<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardMetricFormat: string
{
    case Number = 'number';
    case Currency = 'currency';
    case Percentage = 'percentage';
    case Duration = 'duration';
    case Text = 'text';
}
