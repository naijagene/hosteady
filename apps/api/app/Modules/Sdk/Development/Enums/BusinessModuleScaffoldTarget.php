<?php

namespace App\Modules\Sdk\Development\Enums;

enum BusinessModuleScaffoldTarget: string
{
    case Module = 'module';
    case Entity = 'entity';
    case Api = 'api';
    case Test = 'test';
    case Workflow = 'workflow';
    case Seeder = 'seeder';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
