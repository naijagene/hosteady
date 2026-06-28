<?php

namespace App\Http\Resources;

use App\Modules\Sdk\Dashboard\Data\DashboardWidget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DashboardWidget */
class DashboardWidgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DashboardWidget $widget */
        $widget = $this->resource;

        return $widget->toArray();
    }
}
