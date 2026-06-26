<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Designer\Data\WorkflowNodeTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowNodeTemplate */
class WorkflowNodeTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowNodeTemplate $template */
        $template = $this->resource;

        return $template->toArray();
    }
}
