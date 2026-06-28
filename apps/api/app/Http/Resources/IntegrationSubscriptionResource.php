<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Integration\Data\IntegrationEventSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IntegrationEventSubscription */
class IntegrationSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof IntegrationEventSubscription) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }
}
