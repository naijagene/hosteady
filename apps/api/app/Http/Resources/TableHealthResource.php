<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Table\Data\TableHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TableHealthReport */
class TableHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TableHealthReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
