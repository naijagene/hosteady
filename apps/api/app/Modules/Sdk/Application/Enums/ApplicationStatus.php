<?php

namespace App\Modules\Sdk\Application\Enums;

enum ApplicationStatus: string
{
    case Registered = 'registered';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Archived = 'archived';
}
