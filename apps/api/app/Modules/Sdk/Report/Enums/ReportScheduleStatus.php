<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportScheduleStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Deleted = 'deleted';
}
