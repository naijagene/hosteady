<?php

namespace App\Modules\Sdk\Table\Enums;

enum TableStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
    case Active = 'active';
    case Archived = 'archived';
}
