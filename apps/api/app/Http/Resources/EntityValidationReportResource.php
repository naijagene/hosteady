<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Entity\Data\EntityValidationReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityValidationReport */
class EntityValidationReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EntityValidationReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
