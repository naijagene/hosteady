<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Enums;

enum WorkflowInstallStatus: string
{
    case Installed = 'installed';
    case Upgrading = 'upgrading';
    case RolledBack = 'rolled_back';
    case Uninstalled = 'uninstalled';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
