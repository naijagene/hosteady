<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Development\Data\BusinessModuleHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BusinessModuleHealthReport */
class BusinessModuleHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BusinessModuleHealthReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
