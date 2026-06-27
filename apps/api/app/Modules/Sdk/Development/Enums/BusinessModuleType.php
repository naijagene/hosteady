<?php

namespace App\Modules\Sdk\Development\Enums;

enum BusinessModuleType: string
{
    case Business = 'business';
    case Extension = 'extension';
    case Integration = 'integration';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
