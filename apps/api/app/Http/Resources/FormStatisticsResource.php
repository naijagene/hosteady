<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Form\Data\FormStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormStatistics */
class FormStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FormStatistics $statistics */
        $statistics = $this->resource;

        return $statistics->toArray();
    }
}
