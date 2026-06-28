<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentScanStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Clean = 'clean';
}
