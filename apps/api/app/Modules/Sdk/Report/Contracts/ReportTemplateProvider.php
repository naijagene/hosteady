<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportTemplate;

interface ReportTemplateProvider
{
    public function find(string $moduleKey, string $templateKey): ?ReportTemplate;

    /**
     * @return list<ReportTemplate>
     */
    public function list(?string $moduleKey = null): array;
}
