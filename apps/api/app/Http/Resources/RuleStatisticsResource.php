<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Rules\Data\RuleStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RuleStatistics */
class RuleStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RuleStatistics) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
