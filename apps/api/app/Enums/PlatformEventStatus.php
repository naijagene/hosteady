<?php

namespace App\Enums;

enum PlatformEventStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
}
