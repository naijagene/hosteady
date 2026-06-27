<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
    case Active = 'active';
    case Archived = 'archived';
}
