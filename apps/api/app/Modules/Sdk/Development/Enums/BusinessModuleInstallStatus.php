<?php

namespace App\Modules\Sdk\Development\Enums;

enum BusinessModuleInstallStatus: string
{
    case Installed = 'installed';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Uninstalled = 'uninstalled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
