<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowPreviewPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowPreviewPayload */
class WorkflowDesignerPreviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowPreviewPayload $preview */
        $preview = $this->resource;

        return $preview->toArray();
    }
}
