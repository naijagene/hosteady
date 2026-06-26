<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowInstanceReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowInstanceReference */
class WorkflowInstanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowInstanceReference $instance */
        $instance = $this->resource;

        return $instance->toArray();
    }
}
