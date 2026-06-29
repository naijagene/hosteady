<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Navigation\Data\NavigationHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationHealthReport */
class NavigationHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
