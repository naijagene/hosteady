<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
    case Active = 'active';
    case Archived = 'archived';
}
