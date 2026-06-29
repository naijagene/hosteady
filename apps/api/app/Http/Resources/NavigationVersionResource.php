<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationVersion */
class NavigationVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationVersion) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
