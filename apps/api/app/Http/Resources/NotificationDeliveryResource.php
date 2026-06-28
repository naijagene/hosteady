<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Notification\Data\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationDelivery */
class NotificationDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
