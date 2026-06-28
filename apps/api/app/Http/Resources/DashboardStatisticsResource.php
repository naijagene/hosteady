<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Dashboard\Data\DashboardStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DashboardStatistics */
class DashboardStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DashboardStatistics $statistics */
        $statistics = $this->resource;

        return $statistics->toArray();
    }
}
