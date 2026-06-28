<?php

namespace App\Services\Report;

use App\Models\ReportTemplate as ReportTemplateModel;
use App\Modules\Sdk\Report\Contracts\ReportTemplateProvider;
use App\Modules\Sdk\Report\Data\ReportTemplate;

class DynamicReportTemplateService implements ReportTemplateProvider
{
    public function find(string $moduleKey, string $templateKey): ?ReportTemplate
    {
        $model = ReportTemplateModel::query()
            ->where('module_key', $moduleKey)
            ->where('template_key', $templateKey)
            ->first();

        return $model === null ? null : DynamicReportMapper::toTemplate($model);
    }

    /**
     * @return list<ReportTemplate>
     */
    public function list(?string $moduleKey = null): array
    {
        $query = ReportTemplateModel::query()->orderBy('module_key')->orderBy('template_key');

        if ($moduleKey !== null) {
            $query->where('module_key', $moduleKey);
        }

        return $query->get()
            ->map(fn (ReportTemplateModel $model) => DynamicReportMapper::toTemplate($model))
            ->all();
    }
}
