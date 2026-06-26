<?php

namespace App\Modules\Sdk\Workflow\Enums;

enum WorkflowTriggerType: string
{
    case Manual = 'manual';
    case EntityCreated = 'entity_created';
    case EntityUpdated = 'entity_updated';
    case Schedule = 'schedule';
    case PlatformEvent = 'platform_event';
    case Api = 'api';
    case Runtime = 'runtime';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
