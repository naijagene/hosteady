<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Form\Data\FormHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FormHealthReport */
class FormHealthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FormHealthReport $report */
        $report = $this->resource;

        return $report->toArray();
    }
}
