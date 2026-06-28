<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Notification\Data\NotificationStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationStatistics */
class NotificationStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
