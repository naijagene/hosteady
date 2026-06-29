<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Theme\Data\ThemeVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ThemeVersion */
class ThemeVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof ThemeVersion) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
