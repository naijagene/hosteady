<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportDefinition;

interface ReportRegistry
{
    public function register(mixed $source): ReportDefinition;

    public function update(ReportDefinition $definition): ReportDefinition;

    public function find(string $moduleKey, string $reportKey): ?ReportDefinition;

    public function findByPublicId(string $publicId): ?ReportDefinition;

    /**
     * @return list<ReportDefinition>
     */
    public function list(?string $moduleKey = null): array;
}
