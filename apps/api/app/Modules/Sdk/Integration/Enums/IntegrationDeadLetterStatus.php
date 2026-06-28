<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationDeadLetterStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
