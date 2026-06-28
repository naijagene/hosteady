<?php

namespace App\Services\Report;

use App\Models\ReportDefinition as ReportDefinitionModel;

class DynamicReportWorkflowBridge
{
    public function triggerDefinitionRegisteredBestEffort(ReportDefinitionModel $definition): void
    {
        try {
            // Workflow bridge is best-effort placeholder for M5-006.
        } catch (\Throwable) {
        }
    }

    public function triggerDefinitionUpdatedBestEffort(ReportDefinitionModel $definition): void
    {
        try {
            // Workflow bridge is best-effort placeholder for M5-006.
        } catch (\Throwable) {
        }
    }
}
