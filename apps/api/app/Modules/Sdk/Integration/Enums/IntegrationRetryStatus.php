<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationRetryStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Exhausted = 'exhausted';
}
