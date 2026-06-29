<?php

namespace App\Modules\Sdk\Navigation\Enums;

enum NavigationDefinitionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
