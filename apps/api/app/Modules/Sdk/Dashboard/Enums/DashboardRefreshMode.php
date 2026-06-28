<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardRefreshMode: string
{
    case Manual = 'manual';
    case Auto = 'auto';
    case Realtime = 'realtime';
    case OnLoad = 'on_load';
}
