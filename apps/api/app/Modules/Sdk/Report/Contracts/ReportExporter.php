<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportResult;

interface ReportExporter
{
    public function export(ReportDefinition $definition, ReportExportDefinition $exportDefinition): ReportExportResult;
}
