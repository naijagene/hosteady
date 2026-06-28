<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportScheduleDefinition */
class ReportScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReportScheduleDefinition $schedule */
        $schedule = $this->resource;

        return $schedule->toArray();
    }
}
