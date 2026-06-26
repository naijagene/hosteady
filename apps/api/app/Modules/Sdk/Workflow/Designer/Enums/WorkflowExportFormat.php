<?php

namespace App\Modules\Sdk\Workflow\Designer\Enums;

enum WorkflowExportFormat: string
{
    case HeosJson = 'heos_json';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
