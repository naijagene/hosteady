<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowCompatibilityReport */
class WorkflowPackageCompatibilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowCompatibilityReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
