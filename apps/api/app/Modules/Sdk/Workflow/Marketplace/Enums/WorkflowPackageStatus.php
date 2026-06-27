<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Enums;

enum WorkflowPackageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Deprecated = 'deprecated';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
