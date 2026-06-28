<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Report\Data\ReportHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportHealthReport */
class ReportHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReportHealthReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
