<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Data\WorkflowVersionData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowVersionData */
class WorkflowVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowVersionData $version */
        $version = $this->resource;

        return $version->toArray();
    }
}
