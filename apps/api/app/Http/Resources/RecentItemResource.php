<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Personalization\Data\RecentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof RecentItem) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
