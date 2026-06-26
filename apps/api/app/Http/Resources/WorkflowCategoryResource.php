<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Data\WorkflowCategoryReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowCategoryReference */
class WorkflowCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowCategoryReference $category */
        $category = $this->resource;

        return $category->toArray();
    }
}
