<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowDesignerSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowDesignerSnapshot */
class WorkflowCanvasSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowDesignerSnapshot $snapshot */
        $snapshot = $this->resource;

        return $snapshot->toArray();
    }
}
