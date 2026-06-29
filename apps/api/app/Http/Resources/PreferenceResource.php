<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Personalization\Data\PreferenceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof PreferenceItem) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
