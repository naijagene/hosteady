<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationDeadLetterRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationDeadLetterRecord */
class IntegrationDeadLetterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationDeadLetterRecord) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
