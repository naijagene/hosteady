<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentVersionStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Superseded = 'superseded';
    case Deleted = 'deleted';
}
