<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Table\Data\TableStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TableStatistics */
class TableStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TableStatistics $statistics */
        $statistics = $this->resource;

        return $statistics->toArray();
    }
}
