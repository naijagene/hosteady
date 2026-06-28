<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationHealthReport */
class IntegrationHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationHealthReport) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
