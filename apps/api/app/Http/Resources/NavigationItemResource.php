<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationItem */
class NavigationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationItem) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
