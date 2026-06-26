<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowTimerReference */
class WorkflowTimerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowTimerReference $timer */
        $timer = $this->resource;

        return $timer->toArray();
    }
}
