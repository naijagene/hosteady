<?php

namespace App\Modules\Sdk\Workflow\Designer\Enums;

enum WorkflowCanvasStatus: string
{
    case Draft = 'draft';
    case Saved = 'saved';
    case Archived = 'archived';
}
