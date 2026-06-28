<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportChartType: string
{
    case Bar = 'bar';
    case Line = 'line';
    case Pie = 'pie';
    case Area = 'area';
    case Donut = 'donut';
    case Table = 'table';
    case Kpi = 'kpi';
}
