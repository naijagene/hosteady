<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationPersonalization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationPersonalization */
class NavigationPersonalizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationPersonalization) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
