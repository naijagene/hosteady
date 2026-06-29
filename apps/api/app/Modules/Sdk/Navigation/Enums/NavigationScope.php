<?php

namespace App\Modules\Sdk\Navigation\Enums;

enum NavigationScope: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Application = 'application';
}
