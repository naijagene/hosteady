<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Rules\Data\RuleHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RuleHealthReport */
class RuleHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RuleHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
