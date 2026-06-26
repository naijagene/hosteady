<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowImportResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowImportResult */
class WorkflowImportResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowImportResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}
