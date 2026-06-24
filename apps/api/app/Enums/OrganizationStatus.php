<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
