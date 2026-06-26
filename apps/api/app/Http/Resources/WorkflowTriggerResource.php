<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTriggerReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowTriggerReference */
class WorkflowTriggerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowTriggerReference $trigger */
        $trigger = $this->resource;

        return $trigger->toArray();
    }
}
