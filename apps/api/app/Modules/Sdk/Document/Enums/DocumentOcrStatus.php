<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentOcrStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
