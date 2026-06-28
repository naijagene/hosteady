<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Ui\Data\UiComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UiComponent */
class UiComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof UiComponent) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
