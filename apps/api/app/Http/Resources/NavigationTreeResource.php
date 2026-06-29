<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationTree;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationTree */
class NavigationTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationTree) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
