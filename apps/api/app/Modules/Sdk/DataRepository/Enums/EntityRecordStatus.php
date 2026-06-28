<?php

namespace App\Modules\Sdk\DataRepository\Enums;

enum EntityRecordStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Deleted = 'deleted';
}