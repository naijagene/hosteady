<?php

namespace App\Enums;

enum RoleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Deprecated = 'deprecated';
}
