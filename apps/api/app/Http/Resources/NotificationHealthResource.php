<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Notification\Data\NotificationHealthReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationHealthReport */
class NotificationHealthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
