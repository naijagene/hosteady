<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Modules\Sdk\DataRepository\Data\EntityRecordProjection;

interface EntityRecordProjectionProvider
{
    /**
     * @return array<string, mixed>
     */
    public function project(EntityRecord $record, ?EntityRecordProjection $projection = null): array;
}
