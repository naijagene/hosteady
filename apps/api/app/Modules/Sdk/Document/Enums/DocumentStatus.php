<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
    case Deleted = 'deleted';
}
