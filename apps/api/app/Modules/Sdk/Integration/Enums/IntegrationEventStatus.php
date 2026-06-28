<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationEventStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Routed = 'routed';
    case Replayed = 'replayed';
    case Failed = 'failed';
    case DeadLettered = 'dead_lettered';
}
