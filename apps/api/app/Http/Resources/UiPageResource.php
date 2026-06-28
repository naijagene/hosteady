<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UiPageDefinition */
class UiPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof UiPageDefinition) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
