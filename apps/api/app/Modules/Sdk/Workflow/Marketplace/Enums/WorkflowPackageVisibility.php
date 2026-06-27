<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Enums;

enum WorkflowPackageVisibility: string
{
    case Public = 'public';
    case Organization = 'organization';
    case Private = 'private';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
