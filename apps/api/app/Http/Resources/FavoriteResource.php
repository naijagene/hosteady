<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof FavoriteItem) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
