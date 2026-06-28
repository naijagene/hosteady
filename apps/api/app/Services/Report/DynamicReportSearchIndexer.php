<?php

namespace App\Services\Report;

use App\Models\ReportDefinition as ReportDefinitionModel;

class DynamicReportSearchIndexer
{
    public function indexDefinitionBestEffort(ReportDefinitionModel $definition): void
    {
        try {
            // Search indexing is best-effort placeholder for M5-006.
        } catch (\Throwable) {
        }
    }
}
