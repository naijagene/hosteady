<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Dashboard\Data\DashboardHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DashboardHealthReport */
class DashboardHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DashboardHealthReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
