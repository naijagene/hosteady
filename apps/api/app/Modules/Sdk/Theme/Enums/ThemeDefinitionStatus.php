<?php

namespace App\Modules\Sdk\Theme\Enums;

enum ThemeDefinitionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
