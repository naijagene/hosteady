<?php

namespace App\Modules\Sdk\Report\Contracts;

use App\Modules\Sdk\Report\Data\ReportDefinition;

interface ReportRenderer
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function render(ReportDefinition $definition, array $context = []): array;
}
