<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowExecutionResult */
class WorkflowExecutionResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowExecutionResult $result */
        $result = $this->resource;

        return $result->toArray();
    }
}
