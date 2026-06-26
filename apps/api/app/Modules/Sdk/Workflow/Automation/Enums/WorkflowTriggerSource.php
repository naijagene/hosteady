<?php

namespace App\Modules\Sdk\Workflow\Automation\Enums;

enum WorkflowTriggerSource: string
{
    case Manual = 'manual';
    case PlatformEvent = 'platform_event';
    case EntityCreated = 'entity_created';
    case EntityUpdated = 'entity_updated';
    case Schedule = 'schedule';
    case Api = 'api';
}
