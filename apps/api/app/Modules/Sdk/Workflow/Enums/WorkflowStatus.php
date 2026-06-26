<?php

namespace App\Modules\Sdk\Workflow\Enums;

enum WorkflowStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
