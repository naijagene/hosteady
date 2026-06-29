<?php

namespace App\Modules\Sdk\Theme\Enums;

enum ThemeInheritanceMode: string
{
    case None = 'none';
    case MergeParent = 'merge_parent';
    case OverrideParent = 'override_parent';
}
