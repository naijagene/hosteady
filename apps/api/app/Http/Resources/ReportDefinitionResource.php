<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Report\Data\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReportDefinition */
class ReportDefinitionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReportDefinition $definition */
        $definition = $this->resource;

        return $definition->toArray();
    }
}
