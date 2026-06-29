<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationRenderPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationRenderPayload */
class NavigationRenderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationRenderPayload) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
