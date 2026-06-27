<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Development\Data\BusinessModuleValidationReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessModuleValidationReport */
class BusinessModuleValidationReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BusinessModuleValidationReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
