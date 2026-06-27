<?php

namespace App\Modules\Sdk\Development\Enums;

enum BusinessModuleStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
