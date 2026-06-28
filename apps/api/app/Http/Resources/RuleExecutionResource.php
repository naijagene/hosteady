<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Rules\Data\RuleExecutionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RuleExecutionResult */
class RuleExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RuleExecutionResult) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
