<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Entity\Data\EntityStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntityStatistics */
class EntityStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EntityStatistics $statistics */
        $statistics = $this->resource;

        return $statistics->toArray();
    }
}
