<?php

namespace App\Modules\Sdk\Workflow\Enums;

enum WorkflowVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
