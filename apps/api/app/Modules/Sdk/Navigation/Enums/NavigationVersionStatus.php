<?php

namespace App\Modules\Sdk\Navigation\Enums;

enum NavigationVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
