<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardChartType: string
{
    case Line = 'line';
    case Bar = 'bar';
    case Pie = 'pie';
    case Donut = 'donut';
    case Area = 'area';
    case Scatter = 'scatter';
    case Gauge = 'gauge';
    case Table = 'table';
    case None = 'none';
}
