<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Enums;

enum WorkflowCompatibilityStatus: string
{
    case Compatible = 'compatible';
    case Warning = 'warning';
    case Unsupported = 'unsupported';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
