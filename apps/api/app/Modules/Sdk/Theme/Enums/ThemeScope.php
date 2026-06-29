<?php

namespace App\Modules\Sdk\Theme\Enums;

enum ThemeScope: string
{
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Application = 'application';
}
