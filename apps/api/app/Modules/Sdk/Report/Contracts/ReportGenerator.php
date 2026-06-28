<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Entity\Data\EntityDefinition;

interface ReportGenerator
{
    public function generateEntityReport(string $moduleKey, string $entityKey, string $reportType = 'list'): ReportDefinition;

    public function generateFromEntity(EntityDefinition $entity, string $reportType = 'list'): ReportDefinition;
}
