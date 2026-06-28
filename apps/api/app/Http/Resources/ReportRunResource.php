<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Report\Data\ReportRunResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportRunResult */
class ReportRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReportRunResult $run */
        $run = $this->resource;

        return $run->toArray();
    }
}
