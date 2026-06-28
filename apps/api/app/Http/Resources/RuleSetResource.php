<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RuleSetDefinition */
class RuleSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RuleSetDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
