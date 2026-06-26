<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowExportResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowExportResult */
class WorkflowExportResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowExportResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}
