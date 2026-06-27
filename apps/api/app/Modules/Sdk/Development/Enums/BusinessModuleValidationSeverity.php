<?php

namespace App\Modules\Sdk\Development\Enums;

enum BusinessModuleValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
