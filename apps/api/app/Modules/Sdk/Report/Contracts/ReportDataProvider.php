<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportDataset;
use App\Modules\Sdk\Report\Data\ReportDefinition;

interface ReportDataProvider
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function resolve(ReportDefinition $definition, array $context = []): ReportDataset;
}
