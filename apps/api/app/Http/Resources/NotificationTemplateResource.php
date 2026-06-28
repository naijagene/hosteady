<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Notification\Data\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationTemplate */
class NotificationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
