<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardType: string
{
    case Entity = 'entity';
    case Module = 'module';
    case Custom = 'custom';
    case Analytics = 'analytics';
}
