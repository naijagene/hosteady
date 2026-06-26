<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowValidationReport */
class WorkflowValidationReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowValidationReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
