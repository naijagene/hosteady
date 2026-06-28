<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationDeliveryStatus: string
{
    case Pending = 'pending';
    case Simulating = 'simulating';
    case Completed = 'completed';
    case Failed = 'failed';
    case DeadLettered = 'dead_lettered';
}
