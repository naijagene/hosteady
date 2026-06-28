<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportType: string
{
    case Entity = 'entity';
    case Table = 'table';
    case Dashboard = 'dashboard';
    case Custom = 'custom';
    case Summary = 'summary';
    case List = 'list';
}
