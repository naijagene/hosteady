<?php

namespace App\Enums;

enum PlatformJobPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';
}
