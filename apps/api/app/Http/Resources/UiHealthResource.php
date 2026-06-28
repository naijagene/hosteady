<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Ui\Data\UiHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UiHealthReport */
class UiHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof UiHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
