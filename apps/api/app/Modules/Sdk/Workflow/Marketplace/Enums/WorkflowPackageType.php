<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Enums;

enum WorkflowPackageType: string
{
    case Solution = 'solution';
    case Template = 'template';
    case Bundle = 'bundle';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
