<?php

namespace App\Modules\Sdk\Theme\Enums;

enum ThemeVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
