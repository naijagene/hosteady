<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiPageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
