<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Report\Data\ReportStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportStatistics */
class ReportStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReportStatistics $statistics */
        $statistics = $this->resource;

        return $statistics->toArray();
    }
}
