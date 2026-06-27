<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Form\Data\FormValidationReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormValidationReport */
class FormValidationReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FormValidationReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
