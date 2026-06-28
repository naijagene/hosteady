<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationEventDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case Internal = 'internal';
}
