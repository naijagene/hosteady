<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Report\Data\ReportExportResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportExportResult */
class ReportExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReportExportResult $export */
        $export = $this->resource;

        return $export->toArray();
    }
}
