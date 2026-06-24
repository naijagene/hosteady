<?php

namespace App\Enums;

enum WorkspaceApplicationStatus: string
{
    case Enabling = 'enabling';
    case Active = 'active';
    case Disabled = 'disabled';
    case Archived = 'archived';
    case Removed = 'removed';
}
