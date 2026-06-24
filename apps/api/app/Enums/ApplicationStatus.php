<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Retired = 'retired';
}
