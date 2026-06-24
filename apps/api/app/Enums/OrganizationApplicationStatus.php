<?php

namespace App\Enums;

enum OrganizationApplicationStatus: string
{
    case Installing = 'installing';
    case Active = 'active';
    case Disabled = 'disabled';
    case Uninstalled = 'uninstalled';
}
