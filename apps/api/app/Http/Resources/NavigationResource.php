<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Application\Data\NavigationMenu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NavigationMenu */
class NavigationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof NavigationMenu) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
