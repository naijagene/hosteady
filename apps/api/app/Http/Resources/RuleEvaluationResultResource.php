<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Rules\Data\RuleEvaluationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RuleEvaluationResult */
class RuleEvaluationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RuleEvaluationResult) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
