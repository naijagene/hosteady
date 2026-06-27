<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Entity\Data\EntityHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityHealthReport */
class EntityHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EntityHealthReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
