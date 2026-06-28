<?php

namespace App\Modules\Sdk\Dashboard\Enums;

enum DashboardStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
    case Active = 'active';
    case Archived = 'archived';
}
