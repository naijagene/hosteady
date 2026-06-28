<?php

namespace App\Services\Report;

use App\Models\ReportDefinition as ReportDefinitionModel;
use App\Modules\Sdk\Report\Data\ReportDefinition;

class DynamicReportDefinitionService
{
    public function __construct(
        private readonly DynamicReportRegistryService $registryService,
    ) {
    }

    public function resolve(string $moduleKey, string $reportKey): ReportDefinition
    {
        $definition = $this->registryService->find($moduleKey, $reportKey);

        if ($definition === null) {
            throw new \App\Modules\Sdk\Report\Exceptions\ReportNotFoundException(sprintf(
                'Report definition [%s.%s] was not found.',
                $moduleKey,
                $reportKey,
            ));
        }

        return $definition;
    }

    public function resolveModel(string $moduleKey, string $reportKey): ReportDefinitionModel
    {
        $model = $this->registryService->findModel($moduleKey, $reportKey);

        if ($model === null) {
            throw new \App\Modules\Sdk\Report\Exceptions\ReportNotFoundException(sprintf(
                'Report definition [%s.%s] was not found.',
                $moduleKey,
                $reportKey,
            ));
        }

        return $model;
    }
}
