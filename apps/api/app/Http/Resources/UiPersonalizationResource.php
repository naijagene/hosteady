<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Ui\Data\UiPersonalization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UiPersonalization */
class UiPersonalizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof UiPersonalization) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
