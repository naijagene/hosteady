<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowDefinitionReference */
class WorkflowDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowDefinitionReference $definition */
        $definition = $this->resource;

        return $definition->toArray();
    }
}
