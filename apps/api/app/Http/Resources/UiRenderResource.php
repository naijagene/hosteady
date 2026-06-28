<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Ui\Data\UiRenderPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UiRenderPayload */
class UiRenderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof UiRenderPayload) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
