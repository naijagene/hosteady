<?php

namespace App\Modules\Sdk\Application\Enums;

enum WorkspaceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
